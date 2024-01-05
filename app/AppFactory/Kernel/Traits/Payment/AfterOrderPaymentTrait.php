<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:17
 */

namespace app\AppFactory\Kernel\Traits\Payment;


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
        $this->order['payment_status'] = 2;
        $this->order['payment_time'] = time();
        actionLog($this->order,'订单数据');
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(),'订单修改数据');
        $flag[] = $this->pickup();
        // 结算收费记录
        $flag[] = $this->settlementCharge();
        $flag[] = $this->settlementHosting();
        $result = flag_check($flag);
        $this->sendTemp();
        return $result;
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
     * 现场购买或提货码提货，记录时间，减库存
     * @return int
     */
    protected function pickup()
    {
        $flag[] = 1;
        $this->order['pickup_time'] = time();
        $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
        foreach ($details as $key => $value) {
            $ss = $this->getStoreShelvesFind(['ss_id' => $value['ss_id']]);
            if (!$ss) {
                actionLog($this->getLS(),'查无货架信息');
            }
            $ss = $ss->toArray();
            // 云值守普通订单
            if (in_array($this->order['order_type'],[1,2])) {
                $ss['stock'] = bcsub($ss['stock'],$value['quantity']);
            }
            // 云仓订单
            if (in_array($this->order['order_type'],[3,4])) {
                $ss['frozen_stock'] = bcsub($ss['frozen_stock'],$value['quantity']);
            }
            if ($ss['stock'] <= $ss['warning_stock']) {
                @$this->sendStockNotice($ss);
            }

            // 减库存
            $flag[] = $this->updateStoreShelves($ss);
            actionLog($this->getLS(),'减库存SQL');

        }
        actionLog($flag,'减库存flag');
        return flag_check($flag);
    }

    /**
     * 结算收费记录
     * @return bool
     */
    protected function settlementCharge()
    {
        $where['order_id'] = $this->order['order_id'];
        $where['payment_status'] = 1;
        $sc = $this->storeChargeBe($where);
        if ($sc) {
            $update['mch_no'] = $this->order['mch_no'];
            $update['payment_type'] = $this->order['payment_type'];
            $update['payment_method'] = $this->order['payment_method'];
            $update['payment_status'] = 2;
            $update['payment_time'] = time();
            $update['status'] = 2;
            $result = $this->updateStoreCharge($update,$where);
            actionLog($this->getLS(),'结算收费SQL');
            return $result;
        }
        return true;
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
     * 结算托管费用
     * @return mixed
     */
    protected function settlementHosting()
    {
        $hosting = $this->getStoreHostingFind(['store_id' => $this->order['store_id']],'*','id desc');
        $result = $this->getTotalAmount($hosting,$this->order);
        return $result;
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
        $this->order['payment_status'] = 3;
        $this->order['payment_time'] = time();
        return $this->updateSaleOrders($this->order);
    }
}