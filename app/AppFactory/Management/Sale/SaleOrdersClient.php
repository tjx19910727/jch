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
use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Kernel\Traits\Payment\TlPayTrait;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDailyCountTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Send\MobileNoticeTrait;
use app\AppFactory\Kernel\Traits\Send\WxNoticeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Management\ManagementClient;

class SaleOrdersClient extends ManagementClient
{
    use AuthManagerTrait;
    use SaleOrdersTrait,SaleOrdersRefundTrait,SaleOrdersRevenueTrait,SaleOrdersDailyCountTrait;
    use BeforeOrderPaymentTrait;
    use StrategyMachineTrait;
    use StrategyIncomeTrait;
    use StrategyManagerTrait;
    use UserTrait;
    use WxNoticeTrait;
    use MobileNoticeTrait;
    use StrategyPayeeTrait;
    use BeforeOrderRefundTrait,AfterOrderRefundTrait;
    use WxPayTrait,AliPayTrait,TlPayTrait,JdCashierTrait;

    protected $order;
    protected $sod;
    protected $strategyPayee;
    protected $refundData;

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
        $this->order = $this->getSaleOrdersFind(['order_id' => $postData['order_id']]);
        if (!$this->order) return $this->rFail("查无订单数据");
        $this->order = $this->order->toArray();
        if ($this->order['pay_type'] != 4) return $this->rFail("当前仅支持京东收银收款的订单退款");
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
        $where['sm.s_type'] = 1;
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

        $func_name = $this->refundType[$this->order['pay_type']];
        $result = $this->$func_name();
        $check = obj2arr($result);
        if ($check['state'] == "200") {
            // 支付宝支付、通联支付退款实时处理，不用异步
            if ($this->order['pay_type'] == 3 || $this->order['pay_type'] == 2) {
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
        if ($this->order['pay_type'] == 2 || $this->order['pay_type'] == 3) {
            $flag[] = $this->refundFail();
        }
        return $result;
    }

    /**
     * 获取首页今天昨天销售额、销量
     * @param $where
     * @return array
     */
    public function getData($where)
    {
        $data = [
            "today" => ["saleMoney" => 0.00,"saleQuantity" => 0,'discountMoney' => 0],
            "yesterday" => ["saleMoney" => 0.00,"saleQuantity" => 0,'discountMoney' => 0],
        ];
        $whereToday = $where;
        $whereToday[] = ['create_date','=',strtotime(date("Y-m-d"))];
        $today = $this->getSaleOrdersFind($whereToday,'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney','','create_date');
        if ($today) $today = $today->toArray();

        $whereYesterday = $where;
        $whereYesterday[] = ['create_date', '=', strtotime(date("Y-m-d 00:00:00",strtotime("-1 days")))];
        $yesterday = $this->getSaleOrdersFind($whereYesterday,'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney','','create_date');
        if ($yesterday) $yesterday = $yesterday->toArray();
        if ($today) $data['today'] = $today;
        if ($yesterday) $data['yesterday'] = $yesterday;
        return $data;
    }

    /**
     * 获取销售视图数据
        销售额、销量折线图
        默认1个月内每天的数据
        半年内每周的数据
        1年内每月的数据
     * @param $where
     * @param int $type
     * @return array|string
     */
    public function getChartData($where,$type = 1)
    {
        if ($type == 1) {
            $field = "totalPrice,totalQuantity,countDate";
            $group = "";
            $where[] = ['create_date','>=', strtotime("-1 months")];
        }
        if ($type == 2) {
            $field = "sum(totalPrice) totalPrice, sum(totalQuantity) totalQuantity, DATE_FORMAT(countDate,'Week %v,%x') week";
            $group = "week";
            $where[] = ['create_date','>=', strtotime("-6 months")];
        }
        if ($type == 3) {
            $field = "sum(totalPrice) totalPrice, sum(totalQuantity) totalQuantity, DATE_FORMAT(countDate,'%x-%m') month";
            $group = "month";
            $where[] = ['create_date','>=', strtotime("-1 year")];
        }
        $data = $this->getSaleOrdersDailyCountList($where,0,$field,'',$group);
        return $this->rQ($data);
    }

}