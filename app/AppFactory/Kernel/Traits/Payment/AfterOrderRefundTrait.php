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
    /**
     * 退款成功
     * @return bool
     */
    public function refundSuccess()
    {
        $order_id = 0;
        $sor = $this->getSaleOrdersRefundList(['refund_trade_no' => $this->refundTradeNo,'status' => 1]);
        actionLog($this->getLS(),'退款成功查询退款记录');
        $sor = obj2arr($sor);
        if (!$sor) return $this->r(100,'查无退款记录');
        actionLog($sor,'退款记录');
        foreach ($sor as $key => $value) {
            if (!$order_id) $order_id = $value['order_id'];
            // 退款退分润
            $refundRevenue = $this->refundRevenue($value);
            if ($refundRevenue !== true) return $this->r(100,'退款退分润失败');

            // 修改退款记录
            $flag[] = $this->refundSuccessUpdateSor($value);

            // 退款成功修改订单副表
            $flag[] = $this->refundSuccessUpdateSod($value);
        }
        // 修改订单退款状态
        $flag[] = $this->refundSuccessUpdateOrder($order_id);
        $result = flag_check($flag);
        if (!$result) return $this->r(100,'修改退款记录失败');
        return true;
    }

    /**
     * 退分润
     * @param $refundSor
     * @return bool
     */
    public function refundRevenue($refundSor)
    {
        $whereSor['sod_id'] = $refundSor['sod_id'];
        $whereSor[] = ['status','>',0];
        $revenue = $this->getSaleOrdersRevenueList($whereSor);
        $revenue = obj2arr($revenue);
        // 查分润列表
        if ($revenue) {
            foreach ($revenue as $key => $value) {
                $update['sor_id'] = $value['sor_id'];
                $refundRevenueAmount = bcmul($refundSor['refund_amount'],bcdiv($value['income_value'],100,2),3);
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
    protected function refundSuccessUpdateSor($value)
    {
        $update = [];
        $update['refund_no'] = $this->refund_no;
        $update['sor_id'] = $value['sor_id'];
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
    protected function refundSuccessUpdateSod($value)
    {
        $result = $this->incSaleOrdersDetails(['sod_id' => $value['sod_id']],'refund_quantity',$value['refund_quantity']);
        actionLog($this->getLS(),'修改订单副表退款数量SQL');
        return $result;
    }

    /**
     * 退款成功修改订单
     * @param $order_id
     * @return mixed
     */
    protected function refundSuccessUpdateOrder($order_id)
    {
        $updateOrder = [
            "order_id" => $order_id,
            "refund_status" => 2,
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
        if (!$result) return $this->r(100,'修改退款记录失败');
        return true;
    }
}