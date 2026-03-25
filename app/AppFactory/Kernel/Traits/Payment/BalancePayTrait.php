<?php
/**
 * Created by VSCode.
 * User: lgf
 * Date: 2026/03/24
 * Time: 16:50
 */

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Traits\Card\CardTrait;
use think\facade\Db;

trait BalancePayTrait
{
    use CardTrait;
    use AfterOrderPaymentTrait;

    /**
     * Balance payment method map
     * 1: scan/normal pay entry
     */
    protected $balancePaymentMethod = [
        "1" => "balanceDirectPay",
    ];

    /**
     * Balance pay entry
     * @return array|string
     */
    public function balancePay()
    {
        $func_name = $this->balancePaymentMethod["1"];
        return $this->$func_name();
    }

    /**
     * Deduct card balance and mark order paid
     * @return array|string
     */
    protected function balanceDirectPay()
    {
        $cardNo = trim($this->data['card_no'] ?? '');
        if (!$cardNo) {
            return $this->rFail("余额支付缺少卡号");
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
            if ((int)($this->order['pay_status'] ?? 0) === 3) {
                return $this->r(200, $this->lang("VOrderPay.pay_status3"), [
                    'card_no' => $cardNo,
                    'balance' => $paidLog['balance'] ?? null,
                ]);
            }

            $this->startTrans();
            try {
                $this->order['pay_code'] = $cardNo;
                $flag[] = ($this->order['total_price'] > 0 ? $this->settlementRevenue() : 1);
                $flag[] = $this->paymentSuccessful();
                $result = flag_check($flag);
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
        }

        $this->startTrans();
        try {
            $card = Db::name('card')->where(['card_no' => $cardNo])->lock(true)->find();
            if (!$card) {
                $this->rollbackTrans();
                return $this->rFail("积分卡不存在");
            }

            $before = (string)($card['balance'] ?? '0');
            if (bccomp($before, (string)$amount, 2) < 0) {
                $this->rollbackTrans();
                return $this->rFail("积分卡余额不足");
            }

            $after = bcsub($before, (string)$amount, 2);
            $update = Db::name('card')->where(['card_no' => $cardNo])->update([
                'balance' => $after,
            ]);
            if ($update === false) {
                $this->rollbackTrans();
                return $this->rFail("扣减卡余额失败");
            }

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
                    'remark' => 'order_id:' . $this->order['order_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $logId = $this->addCardBalanceChangeLogs($logInsert);
                if (!$logId) {
                    $this->rollbackTrans();
                    return $this->rFail("记录余额变更日志失败");
                }
            }

            $this->order['pay_code'] = $cardNo;
            $flag[] = ($this->order['total_price'] > 0 ? $this->settlementRevenue() : 1);
            $flag[] = $this->paymentSuccessful();
            $result = flag_check($flag);

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

        $refundAmount = round((float)($this->refundData['refund_amount'] ?? 0), 2);
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
            $card = Db::name('card')->where(['card_no' => $cardNo])->lock(true)->find();
            if (!$card) {
                $this->rollbackTrans();
                return $this->rFail('积分卡不存在');
            }

            $before = (string)($card['balance'] ?? '0');
            $after = bcadd($before, (string)$refundAmount, 2);

            $update = Db::name('card')->where(['card_no' => $cardNo])->update([
                'balance' => $after,
            ]);
            if ($update === false) {
                $this->rollbackTrans();
                return $this->rFail('回退卡余额失败');
            }

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
                'remark' => 'order_id:' . $this->order['order_id'],
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
