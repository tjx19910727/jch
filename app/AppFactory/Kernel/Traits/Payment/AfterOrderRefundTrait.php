<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/16
 * Time: 18:04
 */

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Traits\Card\CardTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
trait AfterOrderRefundTrait
{
    use CardTrait, WcBaseTrait;
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
            
            //修改卡积分 
            // $flag[] = $this->addCardChangeLog();

            $detail = $this->getSaleOrdersDetailsFind(['sod_id' => $this->refund['sod_id']]);
            if($detail['wc_order_no']){
                $flag[] = $this->orderRefundSync2Wc($this->order, $detail);
            }
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
                $update = [];
                $update['sor_id'] = $value['sor_id'];
                // defensive: ensure income_value is numeric
                $incomeValue = isset($value['income_value']) && is_numeric($value['income_value']) ? $value['income_value'] : 0;
                $incomeAmount = isset($value['income_amount']) && is_numeric($value['income_amount']) ? $value['income_amount'] : 0;
                $currentRefundAmount = isset($value['refund_amount']) && is_numeric($value['refund_amount']) ? $value['refund_amount'] : 0;
                $refundRevenueAmountRaw = 0;
                if ($incomeValue > 0) {
                    $refundRevenueAmountRaw = bcmul($this->refund['refund_amount'], bcdiv($incomeValue, 100, 4), 3);
                }
                // 封顶：最多退到应分润金额，不允许累计超退
                $remainRefundable = bcsub($incomeAmount, $currentRefundAmount, 3);
                if (bccomp($remainRefundable, '0', 3) < 0) {
                    $remainRefundable = '0';
                }
                $refundRevenueAmount = $refundRevenueAmountRaw;
                if (bccomp((string)$refundRevenueAmount, (string)$remainRefundable, 3) > 0) {
                    $refundRevenueAmount = $remainRefundable;
                }
                // 待分润，
                $update['refund_amount'] = bcadd($currentRefundAmount, $refundRevenueAmount, 3);
                // 已分润， 电子钱包或其他线上分账方式，减账号电子钱包
                if (intval($value['status']) == 1 && bccomp((string)$refundRevenueAmount, '0', 3) > 0) {
                    // 减电子钱包
                    // older code referenced 'beneficiary' but revenue rows store manager id in 'manager_id'
                    $beneficiaryManager = $value['manager_id'] ?? $value['beneficiary'] ?? 0;
                    if (!$beneficiaryManager) {
                        return $this->r(100,'减分润账号电子钱包余额失败：收益人为空');
                    }
                    $decResult = $this->decAuthManager(['manager_id' => $beneficiaryManager], 'balance', $refundRevenueAmount);
                    if (!$decResult) {
                        return $this->r(100,'减分润账号电子钱包余额失败');
                    }
                }
                // 退款状态为部分退款
                $update['refund_status'] = 2;
                // 退款金额已达到分润金额全部金额，退款状态为已全部退款
                if (bccomp((string)$update['refund_amount'], (string)$incomeAmount, 3) >= 0) {
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
        $update['refund_no'] = $this->refund_no ?? '';
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
        if($this->order['pay_type'] == 9){
            // $refund_points = bcmul($this->order['intergral_rate'], $this->refund['refund_cost_points']);
        // }elseif($this->order['total_points']){
        //     $saleOrdersDetails = $this->getSaleOrdersDetailsFind(['sod_id' => $this->refund['sod_id']]);
        //     if(!$saleOrdersDetails) $refund_points = 0;
        //     $refund_points = bcmul(bcdiv($this->refund['refund_quantity'], $this->saleOrdersDetails['quantity'], 0),$this->saleOrdersDetails['total_sod_points'],2);
        // }
        // if($refund_points){
        
            $flag[] = $this->incSaleOrdersDetails(['sod_id' => $this->refund['sod_id']], 'refund_cost_points', $this->refund['refund_cost_points']);
            actionLog($this->getLS(),'修改订单副表退款积分SQL');
        }
        
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
            'refund_cost_points' => $this->order['refund_cost_points'] + $this->refund['refund_cost_points'] ?? 0
        ];
        actionLog($updateOrder,'修改订单数据');
        $result = $this->updateSaleOrders($updateOrder);
        actionLog($this->getLS(),'修改订单退款状态SQL');
        return $result;
    }


    public function addCardChangeLog(){
        //查询日志，根据订单号找card_no，找不到card_no时，看有没有绑bind_id
        $log_lists = $this->getCardPointsChangeLogsList(['trade_no' => $this->order['trade_no']])->toArray();
        if(empty($log_lists)) return true;
        $card_no_column = array_column($log_lists,'card_no');
        $bind_id_column = array_column($log_lists,'bind_id');
        $card_no_arr = array_filter($card_no_column);
        $bind_id_arr = array_filter($bind_id_column);
        $card_no = $card_no_arr[0] ?? '';
        $bind_id = $bind_id_arr[0] ?? '';
        if(!$card_no && !$bind_id) return true;
        $res = $this->changePoints($card_no, $this->refund['refund_points'], 2, $this->order['trade_no'], '订单退款扣减积分', $bind_id);
        return $res;
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