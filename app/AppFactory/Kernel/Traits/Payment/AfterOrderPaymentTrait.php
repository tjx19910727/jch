<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:17
 */

namespace app\AppFactory\Kernel\Traits\Payment;



use app\AppFactory\AppFactory;
use app\AppFactory\RabbitMq\MqProducer;

trait AfterOrderPaymentTrait
{

    /**
     * 处理支付成功
     */

    /**
     * @var array 分润状态
     */
    protected $revenueStatus = [
        "1" => 2,         // 电子钱包
        "21" => 1,        // 微信企业付款至零钱
        "22" => 1,        // 微信商户转账至零钱
        "3" => 1,         // 支付宝转账至零钱
        "4" => 2,         // 京东收银分账
    ];

    /**
     * 支付成功
     * @return mixed
     */
    public function paymentSuccessful()
    {
        $flag = [];
        if ($this->order['machine_id']) {
            // 发送给设备终端支付成功状态
            $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paySuccess',['trade_no' => $this->order['trade_no']]);
        }
        $this->order['pay_status'] = 3;
        $this->order['pay_time'] = time();
        if ($this->order['order_type'] != 4) {
            $this->outGoods();
        }
        actionLog($this->order,'订单数据');
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(),'订单修改数据');
        $result = flag_check($flag);
//        $this->sendTemp();
        return $result;
    }

    /**
     * 出货
     * @return string
     */
    public function outGoods()
    {
        $details = $this->order['details'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
        if ($details) {
            $contentArr = [];
            $outArr = [];
            // 旧版本数据，待软件更新后删除
            foreach ($details as $k => $v) {
                $dc = [
                    $v['channel_code'],
                    $v['quantity'],
                ];
                $contentArr[$v['channel_position']][] = $dc;
            }
            // 新数据格式
            foreach ($details as $k => $v) {
                $dc = [
                    "channel_code" => $v['channel_code'],
                    "quantity" => $v['quantity'],
                ];
                $outArr[$v['channel_position']][] = $dc;
            }

            $msg_id = uniqid();
            $content = [
                "msgType" => "outGoods",
                "trade_no" => $this->order['trade_no'],
                "main" => $contentArr,
                "outGoods" => $outArr,
            ];
            $content = json_encode($content);
            $data = [
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "machine_id" => $this->order['machine_id'],
                "data" => $content,
            ];
            $data['sign'] = $this->makeSign($data);
            actionLog($data,'下发数据');

            // 生成发送记录
            $insertMqRecord = [
                "m_id" => $this->order['m_id'],
                "machine_id" => $data['machine_id'],
                "machine_name" => $this->order['machine_name'],
                "msg_id" => $msg_id,
                "path" => "outGoods",
                "content" => json_encode($data),
                "from" => 2,
                "type" => 2,
            ];
            $this->addMachineMqRecord($insertMqRecord);
            actionLog($this->getLS(),'生成发送记录');

            $result = MqProducer::dataSend($data,$data['machine_id']);
            actionLog($result,'下发数据结果');
            $this->order['out_status'] = 2;
            return $result;
        }
        return $this->r(100,$this->lang("VOutGoods.details_no_data"));
    }

    /**
     * 发送购买成功通知、销售成功通知
     */
//    protected function sendTemp()
//    {
//        if ($this->order['user_id']) {
//            $data = $this->order;
//            $data['openid'] = $this->getUserValue(['user_id' => $data['user_id'],['type','in',[2,4]]],'openid');
//            if ($data['openid']) {
//                $this->sendPurchaseSuccessfulNotice($data);
//            }
//            $this->sendSalesNotice($this->order);
//        }
//    }

    /**
     * 结算收益
     * @param string $status 分润成功时不能传参，分润失败传3
     * @return int
     */
    protected function settlementRevenue($status = '')
    {
        $flag[] = 1;
        $where['order_id'] = $this->order['order_id'];
        $revenue = $this->getSaleOrdersRevenueList($where);
        if ($revenue) {
            foreach ($revenue as $key => $value) {
                $update['sor_id'] = $value['sor_id'];
                $update['status'] = $status ? : $this->revenueStatus[$value['revenue_type']];
                // 已分润状态，增加分润时间
                if ($update['status'] == 2 || $update['status'] == 3) $update['revenue_time'] = time();
                // 电子钱包
                if ($value['revenue_type'] == 1) {
                    $result = $this->incAuthManager(['manager_id' => $value['manager_id']],'balance',$value['income_amount']);
                    actionLog($result,'增加账号余额结果');
                    actionLog($this->getLS(),'增加账号余额SQL');
                }
                $flag[] = $this->updateSaleOrdersRevenue($update);
                actionLog($this->getLS(),'结算收益SQL');
            }
            actionLog($flag,'结算收益flag');
        }
        return flag_check($flag);
    }

    /**
     * 支付失败处理
     */

    /**
     * 支付失败
     * @return mixed
     */
    public function paymentFailed()
    {
        $this->order['pay_status'] = 3;
        $this->order['pay_time'] = time();
        return $this->updateSaleOrders($this->order);
    }
}