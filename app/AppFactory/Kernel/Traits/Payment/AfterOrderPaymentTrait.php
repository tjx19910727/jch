<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:17
 */

namespace app\AppFactory\Kernel\Traits\Payment;



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
        $this->order['pay_status'] = 3;
        $this->order['pay_time'] = time();
        actionLog($this->order,'订单数据');
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(),'订单修改数据');
        $result = flag_check($flag);
        if ($result) {
            $this->outGoods();
        }
        $this->sendTemp();
        return $result;
    }

    /**
     * 出货
     */
    public function outGoods()
    {
        $details = $this->order['details'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
        if ($details) {
            $contentArr = [];
            foreach ($details as $k => $v) {
                $dc = [
                    $v['channel_code'],
                    $v['quantity'],
                ];
                $contentArr[$v['channel_position']][] = $dc;
            }
            $content = [
                "msgType" => "outGoods",
                "trade_no" => $this->order['trade_no'],
                "main" => $contentArr,
            ];
            $content = json_encode($content);
            $msg_id = uniqid();
            $data = [
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "machine_id" => "test0001",
                "data" => $content,
            ];
            $data['sign'] = $this->makeSign($data);
            actionLog($data,'下发数据');
            MqProducer::dataSend($data,$data['machine_id']);
            // 生成发送记录
            $insertMqRecord = [
                "m_id" => $this->order['m_id'],
                "machine_id" => $data['machine_id'],
                "msg_id" => $msg_id,
                "content" => json_encode($data),
                "from" => 2,
                "type" => 2,
            ];
            $this->addMachineMqRecord($insertMqRecord);
            actionLog($this->getLS(),'生成发送记录');
        }
    }

    /**
     * 发送购买成功通知、销售成功通知
     */
    protected function sendTemp()
    {
        if ($this->order['user_id']) {
            $data = $this->order;
            $data['openid'] = $this->getUserValue(['user_id' => $data['user_id'],['type','in',[2,4]]],'openid');
            if ($data['openid']) {
                $this->sendPurchaseSuccessfulNotice($data);
            }
            $this->sendSalesNotice($this->order);
        }
    }

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
                    $result = $this->incAuthManager(['manager_id' => $value['beneficiary']],'balance',$value['income_amount']);
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