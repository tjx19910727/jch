<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:26
 */

namespace app\AppFactory\Management\Sale;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\AliPayTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\TlPayTrait;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersVideoTrait;
use app\AppFactory\Kernel\Traits\Send\MobileNoticeTrait;
use app\AppFactory\Kernel\Traits\Send\WxNoticeTrait;
use app\AppFactory\Kernel\Traits\Store\StoreChargeTrait;
use app\AppFactory\Kernel\Traits\Store\StoreShelvesTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyChargeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyStoreTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VSaleOrders;

class SaleOrdersClient extends ManagementClient
{
    use AuthManagerTrait;
    use SaleOrdersTrait;
    use SaleOrdersRefundTrait;
    use SaleOrdersRevenueTrait;
    use StoreShelvesTrait;
    use BeforeOrderPaymentTrait;
    use StrategyChargeTrait;
    use StrategyStoreTrait;
    use StrategyIncomeTrait;
    use StrategyManagerTrait;
    use StoreChargeTrait;
    use SaleOrdersVideoTrait;
    use UserTrait;
    use WxNoticeTrait;
    use MobileNoticeTrait;
    use StrategyPayeeTrait;
    use AliPayTrait;
    use TlPayTrait;
    use WxPayTrait;
    use BeforeOrderRefundTrait;
    use AfterOrderRefundTrait;

    protected $order;
    protected $sod;
    protected $strategyPayee;
    protected $refundData;

    /**
     * 查询一条补扣订单数据
     * @param $where
     * @return array|string
     * @throws \Exception
     */
    public function getSupplementaryFind($where)
    {
        $where['supplementary_payment'] = 1;
        $order = $this->getSaleOrdersFind($where);
        if ($order) {
            $order = $order->toArray();
            $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']]);
            return $this->r(200,'查询成功',$order);
        }
        return $this->rFail("查无订单数据");
    }

    /**
     * 生成补扣订单
     * @param $postData
     * @return array|string
     * @throws \Exception
     */
    public function supplementary($postData)
    {
        $order = $this->getSaleOrdersFind(['order_id' => $postData['order_id']],'trade_no,user_id,user_name,store_id,store_name,terminal_no,order_type,payment_type,payment_method,sp_id');
        $order = $order->toArray();
        $order['supplementary_payment'] = 1;
        $order['remark'] = $postData['remark'];
        $order['trade_no'] = "supplementary" . $order['trade_no'];
        $this->startTrans();
        $order_id = $this->addSaleOrders($order);
        $details = $postData['details'];
        $totalCostPrice = 0;
        $totalRetailPrice = 0;
        $totalQuantity = 0;
        foreach ($details as $key => $value) {
            try {
                validate(VSaleOrders::class)->scene('addDetails')->check($value);
            } catch (\Exception $e) {
                $this->rollbackTrans();
                return $this->rValidate($e->getMessage());
            }
            $ss = $this->getStoreShelvesFind(['ss_id' => $value['ss_id']]);
            $ss = $ss->toArray();
            $insertDetails = [
                "order_id" => $order_id,
                "ss_id" => $ss['ss_id'],
                "shelves_number" => $ss['shelves_number'],
                "wg_id" => $ss['wg_id'],
                "goods_id" => $ss['goods_id'],
                "goods_name" => $ss['goods_name'],
                "goods_pic" => $ss['goods_pic'],
                "gc_id" => $ss['goods_c_id'],
                "gc_name" => $ss['goods_c_name'],
                "cost_price" => $ss['cost_price'],
                "retail_price" => $ss['retail_price'],
                "total_sod_price" => bcmul($ss['retail_price'],$value['quantity'],3),
                "quantity" => $value['quantity'],
                "bar_code" => $ss['bar_code'],
                "batch_number" => $ss['batch_number'],
                "manufacture_time" => $ss['manufacture_time'],
                "sell_by_date" => $ss['sell_by_date'],
            ];
            $flag[] = $this->addSaleOrdersDetails($insertDetails);
            $totalCostPrice = bcadd($totalCostPrice,bcmul($insertDetails['cost_price'],$insertDetails['quantity'],3),3);
            $totalRetailPrice = bcadd($totalRetailPrice,$insertDetails['total_sod_price'],3);
            $totalQuantity = bcadd($totalQuantity,$insertDetails['quantity']);
        }
        $flag[] = $this->updateSaleOrders(['order_id' => $order_id,'cost_price' => $totalCostPrice,'total_price' => $totalRetailPrice,'total_quantity' => $totalQuantity]);
        $result = flag_check($flag);
        if ($result) {
            $this->commitTrans();
            $order = $this->getSaleOrdersFind(['order_id' => $order_id]);
            $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order_id]);
            $this->order = $order->toArray();
            $this->order['paymentUrlLink'] = $this->getUrl("/mobile/mini.entrance/index?order_id=" . $order_id . "&store_id=" . $order['store_id']);
            // 检查收费
            $check = $this->checkCharge();
            if ($check !== true) return $check;
            $this->countIncome();
            return $this->r(200,"生成补扣订单成功",$order);
        }
        $this->rollbackTrans();
        return $this->r(100,'生成补扣订单失败');
    }

    /**
     * 保存监控数据
     * @param $postData
     * @return array|string
     */
    public function saveVideo($postData)
    {
        $user = $this->getUserFind(['user_id' => $postData['user_id']]);
        if (!$user) return $this->r(100,"查无会员信息");
        $user = $user->toArray();
        $postData['user_name'] = $user['name'];
        $postData['openid'] = $user['openid'];
        $postData['mobile'] = $user['mobile'];
        return $this->rAction($this->addSaleOrdersVideo($postData));
    }

    /**
     * 获取监控数据
     * @param $where
     * @return array|string
     */
    public function getVideo($where)
    {
        $video = $this->getSaleOrdersVideoFind($where,'*','order_id');
        return $this->rQ($video);
    }

    /**
     * 发送补扣订单微信模板消息通知或手机短信通知
     * @param $postData
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSupplementaryNotice($postData)
    {
        actionLog($postData,'接收到数据');
        $order = $this->getSaleOrdersFind(['order_id' => $postData['order_id']],'store_id,store_name,user_name,user_id');
        if ($order) return $this->rFail('查无订单信息');
        $order = $order->toArray();
        // 模板消息通知
        if ($postData['sendMethod'] == 1) {
            $order['openid'] = $this->getUserValue(['user_id' => $order['user_id']],'openid');

            actionLog($order,'调用模板消息通知前订单数据');
            $result = $this->sendSupplementaryPaymentNotice($order);
            actionLog($result,'发送模板消息结果');
            return $this->r(200,'操作成功',$result);
        }
        // 短信通知
        if ($postData['sendMethod'] == 2) {
            $order['mobile'] = $this->getUserValue(['user_id' => $order['user_id']],'mobile');

            actionLog($order,'调用短信通知前订单数据');
            $result = $this->sendMobileSupplementary($order);
            actionLog($result,'发送手机短信结果');
            return $result;
        }
        return $this->rFail("查无通知类型");
    }

    /**
     * @var array 退款类型
     */
    protected $refundType = [
        "1" => "wxRefund",
        "2" => "aliRefund",
        "3" => "tlRefund",
        "4" => "jdRefund",
    ];

    protected $postData;
    protected $refundTradeNo;
    protected $totalRefundMoney;

    /**
     * 发起订单退款
     * @param $postData
     * @return array|bool|string
     */
    public function refundOrder($postData)
    {
        $this->order = obj2arr($this->getSaleOrdersFind(['order_id' => $postData['order_id']]));
        $check = $this->getSPayee();
        if ($check !== true) {
            return $check;
        }
        $this->startTrans();
        $this->postData = $postData;
        // 生成退款记录
        $flag = $this->createSor();
        if (!is_array($flag)) {
            $this->rollbackTrans();
            return $flag;
        }
        $result = flag_check($flag);
        if ($result) {
            $this->commitTrans();
            // 调用平台退款
            return $this->callRefund();
        }
        $this->rollbackTrans();
        return $this->rFail("退款失败：生成退款记录失败");

    }


    /**
     * 获取收款策略
     * @return array|bool|string
     */
    public function getSPayee()
    {
        $where['ss.s_type'] = 1;
        $where['sp.sp_id'] = $this->order['sp_id'];
        $strategyPayee = $this->getStrategyPayeeContent($where,'sp.*');
        if (!is_array($strategyPayee)) return $strategyPayee;
        if (!$strategyPayee) return $this->rFail("查无收款方配置信息");
        if (!in_array($strategyPayee['payee_type'],array_keys($this->refundType))) {
            return $this->rFail("未定义的支付类型");
        }
        $this->strategyPayee = $strategyPayee;
        return true;
    }

    /**
     * 调用退款平台接口
     * @return bool
     */
    public function callRefund()
    {
        // 调用平台退款接口
        $this->refundData = [
            "order_trade_no" => $this->order['trade_no'],
            "refund_trade_no" => $this->refundTradeNo,
            "refund_amount" => $this->totalRefundMoney,
        ];

        $func_name = $this->refundType[$this->order['payment_type']];
        $result = $this->$func_name();
        $check = obj2arr($result);
        if ($check['state'] == "200") {
            // 支付宝支付、通联支付退款实时处理，不用异步
            if ($this->order['payment_type'] == 3 || $this->order['payment_type'] == 2) {
                $this->startTrans();
                if (isset($check['data']['trxid'])) $this->refund_no = $check['data']['trxid'];
                if (isset($check['data']['trade_no'])) $this->refund_no = $check['data']['trade_no'];
                $end = $this->refundSuccess();
                if ($end !== true) {
                    $this->rollbackTrans();
                    return $end;
                }
                $this->commitTrans();
            }
            return $result;
        }
        if ($this->order['payment_type'] == 2 || $this->order['payment_type'] == 3) {
            $flag[] = $this->refundFail();
        }
        return $result;
    }

    public function testRefundSuccess($data)
    {
        $this->refundTradeNo = $data['data']['reqsn'];
        $this->order = $data['order'];
            // 支付宝支付、通联支付退款实时处理，不用异步
        if ($this->order['payment_type'] == 3 || $this->order['payment_type'] == 2) {
                $this->startTrans();
            if (isset($data['data']['trxid'])) $this->refund_no = $data['data']['trxid'];
            if (isset($data['data']['trade_no'])) $this->refund_no = $data['data']['trade_no'];
            $end = $this->refundSuccess();
            if ($end !== true) {
                $this->rollbackTrans();
                return $end;
            }
                $this->commitTrans();
        }
        return true;
    }
}