<?php
/**
 * Created by VSCode.
 * User: lgf
 * Date: 2026/03/24
 * Time: 16:50
 */

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Traits\Card\CardTrait;

trait BalancePayTrait
{
    use CardTrait;
    use AfterOrderPaymentTrait;

    /**
     * Balance payment method map
     * 10: scan/normal pay entry
     */
    protected $balancePaymentMethod = [
        "10" => "balanceDirectPay",
    ];

    /**
     * Balance pay entry
     * @return array|string
     */
    public function balancePay()
    {
        $func_name = $this->balancePaymentMethod["10"];
        return $this->$func_name();
    }

    /**
     * Deduct card balance and mark order paid
     * @return array|string
     */
    protected function balanceDirectPay()
    {
        $cardNo = $this->order['pay_code'] ?? '';
        if (!$cardNo) {
            return $this->rFail(lang('Vcard.card_no_require'));
        }

        $amount = round(($this->order['total_price'] ?? 0), 2);
        if ($amount < 0) {
            return $this->rFail("订单金额异常");
        }

        // Idempotency: if deduction log already exists for this trade, avoid double deduction.
        $paidLog = $this->getCardBalanceChangeLogs([
            'trade_no' => $this->order['trade_no'],
            'change_type' => 2,
        ], 'id,balance');

        if ($paidLog) {
            if ($this->order['pay_status'] == 3) {
                return $this->r(200, $this->lang("VOrderPay.pay_status3"), [
                    'card_no' => $cardNo,
                    'balance' => $paidLog['balance'] ?? null,
                ]);
            }elseif($this->order['pay_status'] == 1){
                $this->startTrans();
                try {
                    $result = $this->paymentSuccessful();
                    if ($result) {
                        $this->commitTrans();
                        return $this->r(200, $this->lang("VOrderPay.pay_status3"), [
                            'card_no' => $cardNo,
                            'balance' => $paidLog['balance'] ?? null,
                        ]);
                    }
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e, 1);
                    return $this->rTryCatch($e->getMessage());
                }
            }else{
                return $this->rFail("订单状态异常");
            }
        }
        $this->startTrans();
        try {
            $card = $this->getCardFind(['card_no' => $cardNo], 'card_no,bind_id,password');
            if (!$card) {
                $this->rollbackTrans();
                return $this->rFail("积分卡不存在");
            }

            $bindId = trim($this->data['bind_id'] ?? '');
            $needPayPassword = empty($bindId) || empty($card['bind_id']) || ($bindId != $card['bind_id']);
            if ($needPayPassword) {
                $payPassword = trim($this->data['pay_password'] ?? '');
                if (!$payPassword) {
                    $this->rollbackTrans();
                    return $this->r(201, '需要输入支付密码', [
                        'need_pay_password' => 1,
                        'card_no' => $cardNo,
                    ]);
                }
                if(empty($card['password'])){
                    if ($payPassword != '123456') {
                        $this->rollbackTrans();
                        return $this->rFail('默认密码错误');
                    }
                }else{
                    if (md5($payPassword . config('app.salt')) != $card['password']) {
                        $this->rollbackTrans();
                        return $this->rFail('支付密码错误');
                    }
                }

            }

            $summaryBefore = $this->getCardBalanceSummary($cardNo);
            $before = $summaryBefore['available_balance'] ?? '0.00';
            if (bccomp($before, (string)$amount, 2) < 0) {
                $this->rollbackTrans();
                return $this->rFail("余额不足");
            }

            $consumeResult = $this->consumeCardBalanceBuckets($cardNo, $amount);
            if (!$consumeResult) {
                $this->rollbackTrans();
                return $this->rFail("扣减卡余额失败");
            }

            $summaryAfter = $this->getCardBalanceSummary($cardNo);
            $after = $summaryAfter['available_balance'] ?? '0.00';

            if ($amount > 0) {
                $logInsert = [
                    'card_no' => $cardNo,
                    'balance_before_change' => $before,
                    'balance_changed' => $amount,
                    'balance' => $after,
                    'change_type' => 2,
                    'balance_type' => 3,
                    'trade_no' => $this->order['trade_no'],
                    'activity_id' => 0,
                    'reasons' => '设备订单余额支付扣减',
                    'remark' => json_encode([
                        'order_id' => $this->order['order_id'],
                        'allocations' => $consumeResult['allocations'] ?? [],
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $logId = $this->addCardBalanceChangeLogs($logInsert);
                if (!$logId) {
                    $this->rollbackTrans();
                    return $this->rFail("记录余额变更日志失败");
                }
            }
            $result = $this->paymentSuccessful();
            if ($result) {
                $this->commitTrans();
                return $this->r(200, $this->lang("VOrderPay.pay_status3"), [
                    'card_no' => $cardNo,
                    'balance' => $after,
                ]);
            }

            $this->rollbackTrans();
            return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * Refund to card balance
     * @return array|string
     */
    protected function balanceRefund()
    {
        $cardNo = trim($this->order['pay_code'] ?? '');
        if (!$cardNo) {
            return $this->rFail('订单缺少余额支付卡号');
        }

        $refundAmount = round($this->refundData['refund_amount'] ?? 0, 2);
        if ($refundAmount <= 0) {
            return $this->rFail('退款金额异常');
        }

        // Idempotency: same refund trade no should only increase once.
        $refundLog = $this->getCardBalanceChangeLogs([
            'trade_no' => $this->refundData['refund_trade_no'],
            'change_type' => 1,
        ], 'id,balance');
        if ($refundLog) {
            return $this->r(200, '退款申请成功', [
                'card_no' => $cardNo,
                'balance' => $refundLog['balance'] ?? null,
            ]);
        }

        $this->startTrans();
        try {
            $card = $this->getCardFind(['card_no' => $cardNo], 'card_no');
            if (!$card) {
                $this->rollbackTrans();
                return $this->rFail('积分卡不存在');
            }

            $summaryBefore = $this->getCardBalanceSummary($cardNo);
            $before = $summaryBefore['available_balance'] ?? '0.00';

            $payLog = $this->getCardBalanceChangeLogs([
                'trade_no' => $this->order['trade_no'],
                'change_type' => 2,
            ], 'id,remark');
            if (!$payLog) {
                $this->rollbackTrans();
                return $this->rFail('未找到原支付扣减记录，无法退款');
            }

            $allocations = [];
            $remarkArr = json_decode($payLog['remark'] ?? '', true);
            if (is_array($remarkArr) && !empty($remarkArr['allocations']) && is_array($remarkArr['allocations'])) {
                $allocations = $remarkArr['allocations'];
            }

            $restoreResult = $this->restoreCardBalanceBucketsByAllocations($cardNo, $allocations, $refundAmount);
            $summaryAfter = $this->getCardBalanceSummary($cardNo);
            $after = $summaryAfter['available_balance'] ?? '0.00';

            $logInsert = [
                'card_no' => $cardNo,
                'balance_before_change' => $before,
                'balance_changed' => $refundAmount,
                'balance' => $after,
                'change_type' => 1,
                'balance_type' => 5,
                'trade_no' => $this->refundData['refund_trade_no'],
                'activity_id' => 0,
                'reasons' => '订单退款返还余额',
                'remark' => json_encode([
                    'order_id' => $this->order['order_id'],
                    'restored' => $restoreResult['restored'] ?? [],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $logId = $this->addCardBalanceChangeLogs($logInsert);
            if (!$logId) {
                $this->rollbackTrans();
                return $this->rFail('记录余额退款日志失败');
            }

            $this->commitTrans();
            return $this->r(200, '退款申请成功', [
                'card_no' => $cardNo,
                'balance' => $after,
            ]);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}
