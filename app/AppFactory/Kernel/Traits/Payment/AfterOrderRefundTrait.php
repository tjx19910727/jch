<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/16
 * Time: 18:04
 */

namespace app\AppFactory\Kernel\Traits\Payment;


trait AfterOrderRefundTrait
{
    protected $refund;

    /**
     * 退款成功
     * @return bool
     */
    public function refundSuccess()
    {
        $sor = $this->getSaleOrdersRefundList(['refund_trade_no' => $this->refundTradeNo,'status' => 1]);
        actionLog($this->getLS(),'退款成功查询退款记录');
        $sor = obj2arr($sor);
        if (!$sor) return $this->r(100,'查无退款记录');
        actionLog($sor,'退款记录');
        $quantity = 0;
        foreach ($sor as $key => $value) {
            $this->refund = obj2arr($value);
            if (!$quantity) $quantity = $value['refund_quantity'];
            if (!$this->order) $this->order = $this->getSaleOrdersFind(['order_id' => $value['order_id']]);
            // 退款退分润
            $refundRevenue = $this->refundRevenue();
            if ($refundRevenue !== true) return $this->r(100,'退款退分润失败');

            // 修改退款记录
            $flag[] = $this->refundSuccessUpdateSor();

            // 退款成功修改订单副表
            $flag[] = $this->refundSuccessUpdateSod();
        }
        $this->order['refund_quantity'] += $quantity;
        // 修改订单退款状态
        $flag[] = $this->refundSuccessUpdateOrder();
        actionLog($flag,'退款处理结果');
        $result = flag_check($flag);
        if (!$result) return $this->r(100,'修改退款记录失败');
        return true;
    }

    /**
     * 退分润
     * @return bool
     */
    public function refundRevenue()
    {
        $whereSor['sod_id'] = $this->refund['sod_id'];
        $whereSor[] = ['status','>',0];
        $revenue = $this->getSaleOrdersRevenueList($whereSor);
        $revenue = obj2arr($revenue);
        // 查分润列表
        if ($revenue) {
            foreach ($revenue as $key => $value) {
                $update['sor_id'] = $value['sor_id'];
                $refundRevenueAmount = bcmul($this->refund['refund_amount'],bcdiv($value['income_value'],100,2),3);
                // 待分润，
                $update['refund_amount'] = bcadd($value['refund_amount'],$refundRevenueAmount,3);
                // 已分润， 电子钱包或其他线上分账方式，减账号电子钱包
                if ($value['status'] == 2) {
                    // 减电子钱包
                    $decResult = $this->decAuthManager(['manager_id' => $value['beneficiary']],'balance',$refundRevenueAmount);
                    if (!$decResult) {
                        return $this->r(100,'减分润账号电子钱包余额失败');
                    }
                }
                // 退款状态为部分退款
                $update['refund_status'] = 2;
                // 退款金额已达到分润金额全部金额，退款状态为已全部退款
                if ($update['refund_amount'] >= $value['income_amount']) {
                    $update['refund_status'] = 3;
                }
                $updateResult = $this->updateSaleOrdersRevenue($update);
                actionLog($this->getLS(),'修改订单分润SQL');
                if (!$updateResult) {
                    return $this->r(100,'修改订单分润失败');
                }
            }
        }
        return true;
    }

    /**
     * 退款成功修改退款记录
     * @param $value
     * @return mixed
     */
    protected function refundSuccessUpdateSor()
    {
        $update = [];
        $update['refund_no'] = $this->refund_no;
        $update['sor_id'] = $this->refund['sor_id'];
        $update['status'] = 2;
        $result = $this->updateSaleOrdersRefund($update);
        actionLog($this->getLS(),'修改订单退款记录SQL');
        return $result;
    }

    /**
     * 退款成功修改订单副表
     * @param $value
     * @return mixed
     */
    protected function refundSuccessUpdateSod()
    {
        $flag[] = $this->incSaleOrdersDetails(['sod_id' => $this->refund['sod_id']],'refund_quantity',$this->refund['refund_quantity']);
        actionLog($this->getLS(),'修改订单副表退款数量SQL');
        $flag[] = $this->incSaleOrdersDetails(['sod_id' => $this->refund['sod_id']],'refund_amount',$this->refund['refund_amount']);
        actionLog($this->getLS(),'修改订单副表退款金额SQL');
        $result = $this->checkFlag($flag);
        return $result;
    }

    /**
     * 退款成功修改订单
     * @param $order_id
     * @return mixed
     */
    protected function refundSuccessUpdateOrder()
    {
        $updateOrder = [
            "order_id" => $this->order['order_id'],
            "refund_status" => 2,
            "refund_amount" => $this->order['refund_amount'] + $this->data['refundAmount'],
            "refund_quantity" => $this->order['refund_quantity'],
        ];
        actionLog($updateOrder,'修改订单数据');
        $result = $this->updateSaleOrders($updateOrder);
        actionLog($this->getLS(),'修改订单退款状态SQL');
        return $result;
    }




    /**
     * 退款失败
     * @return bool
     */
    public function refundFail()
    {
        $result = $this->updateSaleOrdersRefund(['status' => 3],['refund_no' => $this->refundTradeNo]);
        actionLog($this->getLS(),'修改退款记录退款失败');
        if (!$result) return $this->r(100,'修改退款记录失败');
        return true;
    }
}