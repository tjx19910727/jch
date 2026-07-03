<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/16
 * Time: 18:04
 */

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Model\Revenue\RevenueOrderRefundModel;
use app\AppFactory\Kernel\Traits\Card\CardTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use think\facade\Db;
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
        $this->refund = $this->buildBusinessRefund($sor);
        if (!$this->order) $this->order = $this->getSaleOrdersFind(['order_id' => $this->refund['order_id']]);
        if ($this->order && !is_array($this->order)) $this->order = $this->order->toArray();

        // 退款退分账，按业务退款单执行一次，避免多条分账明细重复回退。
        $refundRevenue = $this->refundRevenue();
        if ($refundRevenue !== true) return $this->r(100,'退款退分润失败');

        foreach ($sor as $key => $value) {
            $this->refund = obj2arr($value);
            // 修改退款记录
            $flag[] = $this->refundSuccessUpdateSor();
        }

        $this->refund = $this->buildBusinessRefund($sor);
        // 退款成功修改订单副表
        $flag[] = $this->refundSuccessUpdateSod();
        $detail = $this->getSaleOrdersDetailsFind(['sod_id' => $this->refund['sod_id']]);
        if($detail['wc_order_no']){
            $flag[] = $this->orderRefundSync2Wc($this->order, $detail);
        }

        $this->order['refund_quantity'] += $this->refund['refund_quantity'];
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
        $refundList = RevenueOrderRefundModel::where([
                'refund_trade_no' => $this->refundTradeNo,
                'status' => 1,
            ])
            ->select()
            ->toArray();
        if ($refundList) {
            foreach ($refundList as $key => $refundItem) {
                $value = Db::name('revenue_order')->where(['ro_id' => intval($refundItem['ro_id'])])->find();
                if (!$value || intval($value['status'] ?? 0) <= 0) {
                    RevenueOrderRefundModel::update([
                        'ror_id' => intval($refundItem['ror_id']),
                        'status' => 3,
                    ]);
                    return $this->r(100,'查无可退款分账订单');
                }
                $update = [];
                $update['update_time'] = time();
                $incomeAmount = isset($value['income_amount']) && is_numeric($value['income_amount']) ? $value['income_amount'] : 0;
                $currentRefundAmount = isset($value['refund_amount']) && is_numeric($value['refund_amount']) ? $value['refund_amount'] : 0;
                $refundRevenueAmountRaw = isset($refundItem['refund_amount']) && is_numeric($refundItem['refund_amount']) ? $refundItem['refund_amount'] : 0;
                $remainRefundable = bcsub($incomeAmount, $currentRefundAmount, 3);
                if (bccomp($remainRefundable, '0', 3) < 0) {
                    $remainRefundable = '0';
                }
                $refundRevenueAmount = $refundRevenueAmountRaw;
                if (bccomp((string)$refundRevenueAmount, (string)$remainRefundable, 3) > 0) {
                    $refundRevenueAmount = $remainRefundable;
                }
                $update['refund_amount'] = bcadd($currentRefundAmount, $refundRevenueAmount, 3);
                if (intval($value['status']) == 1
                    && ($value['account_type'] ?? '') === 'balance'
                    && bccomp((string)$refundRevenueAmount, '0', 3) > 0) {
                    if (empty($value['manager_id'])) {
                        return $this->r(100,'减分润账号电子钱包余额失败：收益人为空');
                    }
                    $decResult = $this->decAuthManager(['manager_id' => $value['manager_id']], 'balance', $refundRevenueAmount);
                    if (!$decResult) {
                        return $this->r(100,'减分润账号电子钱包余额失败');
                    }
                }
                $updateResult = Db::name('revenue_order')->where(['ro_id' => $value['ro_id']])->update($update);
                actionLog($this->getLS(),'修改新分账订单退款SQL');
                if (!$updateResult) {
                    return $this->r(100,'修改新分账订单失败');
                }
                RevenueOrderRefundModel::update([
                    'ror_id' => intval($refundItem['ror_id']),
                    'status' => 2,
                ]);
            }
        }
        return true;
    }

    protected function calcAfterRevenueRefundAmount(array $revenue, $incomeValue, $incomeAmount)
    {
        if ($incomeAmount <= 0) return 0;
        // 商品按件固定金额分账应按退款数量回退，避免折扣影响退款分账金额。
        if (intval($revenue['rule_mode'] ?? 0) === 4
            && intval($revenue['calc_type'] ?? 0) === 2
            && intval($revenue['sod_id'] ?? 0) === intval($this->refund['sod_id'] ?? 0)
            && floatval($revenue['sod_quantity'] ?? 0) > 0
            && floatval($this->refund['refund_quantity'] ?? 0) > 0) {
            return bcmul(
                bcdiv($this->refund['refund_quantity'], $revenue['sod_quantity'], 6),
                $incomeAmount,
                3
            );
        }
        if (intval($revenue['sod_id'] ?? 0) === intval($this->refund['sod_id'])
            && !empty($revenue['sod_total_price'])
            && $revenue['sod_total_price'] > 0) {
            return bcmul($this->refund['refund_amount'], bcdiv($incomeAmount, $revenue['sod_total_price'], 6), 3);
        }
        $calcType = intval($revenue['calc_type'] ?? 0);
        if (in_array($calcType, [1, 4], true) && $incomeValue > 0) {
            return bcmul($this->refund['refund_amount'], bcdiv($incomeValue, 100, 4), 3);
        }
        $orderAmount = is_numeric($this->order['total_price'] ?? null) ? $this->order['total_price'] : 0;
        if ($orderAmount <= 0) return 0;
        return bcmul($this->refund['refund_amount'], bcdiv($incomeAmount, $orderAmount, 6), 3);
    }

    protected function buildBusinessRefund(array $sor)
    {
        $summary = [];
        foreach ($sor as $key => $value) {
            $value = obj2arr($value);
            if (!$summary) {
                $summary = $value;
                $summary['refund_amount'] = 0;
                $summary['refund_cost_points'] = 0;
                $summary['refund_points'] = 0;
            }
            $summary['refund_amount'] = bcadd($summary['refund_amount'], $value['refund_amount'] ?? 0, 3);
            $summary['refund_cost_points'] = bcadd($summary['refund_cost_points'], $value['refund_cost_points'] ?? 0, 3);
            $summary['refund_points'] = bcadd($summary['refund_points'], $value['refund_points'] ?? 0, 3);
        }
        return $summary;
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
        $result = $this->updateSaleOrdersRefund(['status' => 3],['refund_trade_no' => $this->refundTradeNo]);
        RevenueOrderRefundModel::where(['refund_trade_no' => $this->refundTradeNo, 'status' => 1])
            ->update(['status' => 3, 'update_time' => time()]);
        actionLog($this->getLS(),'修改退款记录退款失败');
        if (!$result) return $this->r(100,'修改退款记录失败');
        return true;
    }
}
