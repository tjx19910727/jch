<?php

namespace app\AppFactory\Kernel\Support\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeLogModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeOrderDetailModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeOrderModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeRefundModel;
use think\facade\Db;

/**
 * 设备服务费领域规则。
 *
 * 支付回调和管理端退款共用这里的事务规则，但不复用设备销售订单的
 * paymentSuccessful/settlementRevenue/outGoods 链路。
 */
class MachineServiceFeeService
{
    const PAY_PENDING = 1;
    const PAY_PROCESSING = 2;
    const PAY_SUCCESS = 3;
    const PAY_CLOSED = 4;
    const PAY_FAILED = 5;

    const REFUND_NONE = 0;
    const REFUND_PROCESSING = 1;
    const REFUND_SUCCESS = 2;
    const REFUND_FAILED = 3;

    /**
     * 未配置记录或年费为0的设备不受限；正年费设备必须在有效期内。
     */
    public static function getMachineState($fee, $now = null)
    {
        $now = $now ?: time();
        if (!$fee) {
            return [
                'configured' => 0,
                'annual_fee_cent' => '0.00',
                'annual_fee' => '0.00',
                'service_expire_at' => 0,
                'service_expire_time' => '',
                'service_fee_status' => 'unconfigured',
                'service_fee_status_desc' => '未配置',
                'service_available' => 1,
            ];
        }

        $fee = is_object($fee) && method_exists($fee, 'toArray') ? $fee->toArray() : (array)$fee;
        $annualFeeYuan = self::normalizeYuan($fee['annual_fee_cent'] ?? 0);
        $expireAt = intval($fee['service_expire_at'] ?? 0);
        if (!self::isPositiveYuan($annualFeeYuan)) {
            $status = 'free';
            $statusDesc = '免服务费';
            $available = 1;
        } elseif ($expireAt > $now) {
            $status = 'active';
            $statusDesc = '服务中';
            $available = 1;
        } else {
            $status = 'expired';
            $statusDesc = '已到期';
            $available = 0;
        }

        return [
            'configured' => 1,
            'annual_fee_cent' => $annualFeeYuan,
            'annual_fee' => $annualFeeYuan,
            'service_expire_at' => $expireAt,
            'service_expire_time' => $expireAt ? date('Y-m-d H:i:s', $expireAt) : '',
            'service_fee_status' => $status,
            'service_fee_status_desc' => $statusDesc,
            'service_available' => $available,
        ];
    }

    /**
     * 业务金额统一使用元并保留两位小数，禁止使用float参与计算。
     */
    public static function normalizeYuan($amount)
    {
        $amount = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException('设备服务费金额最多保留两位小数');
        }
        list($integer) = explode('.', $amount, 2);
        if (strlen($integer) > 12) {
            throw new \InvalidArgumentException('设备服务费金额超出允许范围');
        }
        return bcadd($amount, '0', 2);
    }

    public static function addYuan($left, $right)
    {
        return bcadd(self::normalizeYuan($left), self::normalizeYuan($right), 2);
    }

    public static function multiplyYuan($amount, $quantity)
    {
        $quantity = intval($quantity);
        if ($quantity < 0) {
            throw new \InvalidArgumentException('设备服务费计算数量无效');
        }
        return bcmul(self::normalizeYuan($amount), (string)$quantity, 2);
    }

    public static function isPositiveYuan($amount)
    {
        return bccomp(self::normalizeYuan($amount), '0.00', 2) > 0;
    }

    public static function moneyEquals($left, $right)
    {
        return bccomp(self::normalizeYuan($left), self::normalizeYuan($right), 2) === 0;
    }

    /**
     * 微信支付V2协议金额使用整数分，仅允许在微信网关边界转换。
     */
    public static function yuanToWxCent($amount)
    {
        $amount = self::normalizeYuan($amount);
        list($integer, $decimal) = array_pad(explode('.', $amount, 2), 2, '00');
        return intval($integer) * 100 + intval(str_pad($decimal, 2, '0'));
    }

    public static function wxCentToYuan($cent)
    {
        $cent = trim((string)$cent);
        if (!preg_match('/^\d+$/', $cent)) {
            throw new \InvalidArgumentException('微信支付金额格式无效');
        }
        return bcdiv($cent, '100', 2);
    }

    /**
     * 按自然年增加期限，2月29日落到目标年的2月最后一天。
     */
    public static function addNaturalYears($timestamp, $years)
    {
        $timestamp = intval($timestamp);
        $years = intval($years);
        if ($timestamp <= 0 || $years <= 0) {
            throw new \InvalidArgumentException('续费基准时间或续费年数无效');
        }

        $month = intval(date('n', $timestamp));
        $day = intval(date('j', $timestamp));
        $targetYear = intval(date('Y', $timestamp)) + $years;
        $lastDay = intval(date('t', mktime(12, 0, 0, $month, 1, $targetYear)));
        $day = min($day, $lastDay);

        return mktime(
            intval(date('H', $timestamp)),
            intval(date('i', $timestamp)),
            intval(date('s', $timestamp)),
            $month,
            $day,
            $targetYear
        );
    }

    /**
     * 支付成功后才锁定设备并计算真实期限。
     * 同一设备多个待支付订单均可创建；之后每成功支付一笔都会顺延。
     */
    public static function completePayment($orderNo, $thirdTradeNo, $paidAt, $paidAmountYuan)
    {
        Db::startTrans();
        try {
            $order = MachineServiceFeeOrderModel::where('order_no', $orderNo)->lock(true)->find();
            if (!$order) {
                throw new \RuntimeException('设备服务费续费订单不存在');
            }
            if (!self::moneyEquals($order['total_amount_cent'], $paidAmountYuan)) {
                throw new \RuntimeException('设备服务费回调金额与订单不一致');
            }
            if (intval($order['pay_status']) === self::PAY_SUCCESS) {
                Db::commit();
                return ['idempotent' => true, 'm_ids' => []];
            }

            $details = MachineServiceFeeOrderDetailModel::where('msfo_id', intval($order['msfo_id']))
                ->order('m_id asc')->lock(true)->select();
            if (!$details || count($details) !== intval($order['device_count'])) {
                throw new \RuntimeException('设备服务费订单明细不完整');
            }
            $detailAmountYuan = '0.00';
            foreach ($details as $detail) {
                $detailAmountYuan = self::addYuan($detailAmountYuan, $detail['amount_cent']);
                if (intval($detail['renew_years']) <= 0) {
                    throw new \RuntimeException('设备服务费续费年数无效');
                }
            }
            if (!self::moneyEquals($detailAmountYuan, $order['total_amount_cent'])) {
                throw new \RuntimeException('设备服务费订单明细金额与总额不一致');
            }

            $paidAt = intval($paidAt) > 0 ? intval($paidAt) : time();
            $now = time();
            $mIds = [];
            foreach ($details as $detail) {
                $mId = intval($detail['m_id']);

                // 设备状态行是续期和退款之间的并发闸门。
                $fee = MachineServiceFeeModel::where('m_id', $mId)->lock(true)->find();
                if (!$fee || !self::isPositiveYuan($fee['annual_fee_cent'])) {
                    throw new \RuntimeException('设备服务年费已变更为免费，请人工核对该笔支付');
                }

                // 退款中的旧续费先完成期限回退，再让支付平台重试本次回调。
                $refundInProgress = MachineServiceFeeOrderDetailModel::alias('d')
                    ->join('machine_service_fee_order o', 'o.msfo_id=d.msfo_id')
                    ->where('d.m_id', $mId)
                    ->where('o.pay_status', self::PAY_SUCCESS)
                    ->where('o.refund_status', self::REFUND_PROCESSING)
                    ->lock(true)
                    ->count();
                if ($refundInProgress) {
                    throw new \RuntimeException('设备存在处理中的服务费退款');
                }

                $oldExpireAt = intval($fee['service_expire_at']);
                $baseAt = max($oldExpireAt, $paidAt);
                $newExpireAt = self::addNaturalYears($baseAt, intval($detail['renew_years']));

                $detail->save([
                    'effective_old_expire_at' => $oldExpireAt,
                    'effective_start_at' => $baseAt,
                    'effective_end_at' => $newExpireAt,
                    'old_grace_used' => intval($fee['grace_used']),
                    'old_grace_granted_at' => intval($fee['grace_granted_at']),
                    'update_time' => $now,
                ]);
                $fee->save([
                    'service_expire_at' => $newExpireAt,
                    'grace_used' => 0,
                    'grace_granted_at' => 0,
                    'update_time' => $now,
                ]);
                self::addLog([
                    'm_id' => $mId,
                    'machine_id' => (string)$detail['machine_id'],
                    'action' => 'renew',
                    'old_fee_cent' => self::normalizeYuan($fee['annual_fee_cent']),
                    'new_fee_cent' => self::normalizeYuan($fee['annual_fee_cent']),
                    'old_expire_at' => $oldExpireAt,
                    'new_expire_at' => $newExpireAt,
                    'order_no' => $orderNo,
                    'remark' => '支付回调实际顺延' . intval($detail['renew_years']) . '年',
                    'create_time' => $now,
                ]);
                $mIds[] = $mId;
            }

            $order->save([
                'pay_status' => self::PAY_SUCCESS,
                'third_trade_no' => (string)$thirdTradeNo,
                'paid_at' => $paidAt,
                'update_time' => $now,
            ]);
            Db::commit();
            return ['idempotent' => false, 'm_ids' => array_values(array_unique($mIds))];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 创建整单全额退款申请，并锁定当前续费链。仅最后一段续期可退。
     */
    public static function beginFullRefund($orderNo, $reason, $creatorId)
    {
        Db::startTrans();
        try {
            $order = MachineServiceFeeOrderModel::where('order_no', $orderNo)->lock(true)->find();
            if (!$order || intval($order['pay_status']) !== self::PAY_SUCCESS) {
                throw new \RuntimeException('只有支付成功的设备服务费订单可退款');
            }
            if (intval($order['refund_status']) === self::REFUND_SUCCESS) {
                throw new \RuntimeException('该设备服务费订单已退款');
            }
            if (intval($order['refund_status']) === self::REFUND_PROCESSING) {
                $existing = MachineServiceFeeRefundModel::where('msfo_id', intval($order['msfo_id']))
                    ->where('status', self::REFUND_PROCESSING)->order('msfr_id desc')->find();
                Db::commit();
                return ['idempotent' => true, 'order' => $order->toArray(), 'refund' => $existing ? $existing->toArray() : []];
            }

            $details = MachineServiceFeeOrderDetailModel::where('msfo_id', intval($order['msfo_id']))
                ->order('m_id asc')->lock(true)->select();
            if (!$details || count($details) !== intval($order['device_count'])) {
                throw new \RuntimeException('设备服务费订单明细不存在');
            }
            foreach ($details as $detail) {
                $fee = MachineServiceFeeModel::where('m_id', intval($detail['m_id']))->lock(true)->find();
                if (!$fee || intval($fee['service_expire_at']) !== intval($detail['effective_end_at'])) {
                    throw new \RuntimeException('该订单之后设备期限已变化，请先退后续费订单');
                }
            }

            $now = time();
            $refundNo = self::makeNo('SFR');
            $refund = MachineServiceFeeRefundModel::create([
                'refund_no' => $refundNo,
                'msfo_id' => intval($order['msfo_id']),
                'order_no' => (string)$order['order_no'],
                'pay_channel' => (string)$order['pay_channel'],
                'refund_amount_cent' => self::normalizeYuan($order['total_amount_cent']),
                'reason' => mb_substr(trim((string)$reason), 0, 255),
                'status' => self::REFUND_PROCESSING,
                'creator_id' => intval($creatorId),
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $order->save(['refund_status' => self::REFUND_PROCESSING, 'update_time' => $now]);
            MachineServiceFeeOrderDetailModel::where('msfo_id', intval($order['msfo_id']))
                ->update(['refund_status' => self::REFUND_PROCESSING, 'update_time' => $now]);

            Db::commit();
            return ['idempotent' => false, 'order' => $order->toArray(), 'refund' => $refund->toArray()];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 三方退款成功后回退服务期限。若后续续费已生效则拒绝自动回退。
     */
    public static function completeRefund($refundNo, $thirdRefundNo = '')
    {
        Db::startTrans();
        try {
            $refund = MachineServiceFeeRefundModel::where('refund_no', $refundNo)->lock(true)->find();
            if (!$refund) {
                throw new \RuntimeException('设备服务费退款记录不存在');
            }
            if (intval($refund['status']) === self::REFUND_SUCCESS) {
                Db::commit();
                return ['idempotent' => true, 'm_ids' => []];
            }

            $order = MachineServiceFeeOrderModel::where('msfo_id', intval($refund['msfo_id']))->lock(true)->find();
            $details = MachineServiceFeeOrderDetailModel::where('msfo_id', intval($refund['msfo_id']))
                ->order('m_id asc')->lock(true)->select();
            $now = time();
            $mIds = [];
            foreach ($details as $detail) {
                $fee = MachineServiceFeeModel::where('m_id', intval($detail['m_id']))->lock(true)->find();
                if (!$fee || intval($fee['service_expire_at']) !== intval($detail['effective_end_at'])) {
                    throw new \RuntimeException('退款成功但设备期限已被后续续费改变，需要人工回退');
                }
                $oldExpireAt = intval($fee['service_expire_at']);
                $restoredExpireAt = intval($detail['effective_old_expire_at']);
                $fee->save([
                    'service_expire_at' => $restoredExpireAt,
                    'grace_used' => intval($detail['old_grace_used']),
                    'grace_granted_at' => intval($detail['old_grace_granted_at']),
                    'update_time' => $now,
                ]);
                $detail->save([
                    'refund_status' => self::REFUND_SUCCESS,
                    'refunded_at' => $now,
                    'update_time' => $now,
                ]);
                self::addLog([
                    'm_id' => intval($detail['m_id']),
                    'machine_id' => (string)$detail['machine_id'],
                    'action' => 'refund',
                    'old_fee_cent' => self::normalizeYuan($fee['annual_fee_cent']),
                    'new_fee_cent' => self::normalizeYuan($fee['annual_fee_cent']),
                    'old_expire_at' => $oldExpireAt,
                    'new_expire_at' => $restoredExpireAt,
                    'order_no' => (string)$order['order_no'],
                    'refund_no' => $refundNo,
                    'remark' => '整单全额退款回退服务期限',
                    'create_time' => $now,
                ]);
                $mIds[] = intval($detail['m_id']);
            }

            $order->save(['refund_status' => self::REFUND_SUCCESS, 'update_time' => $now]);
            $refund->save([
                'status' => self::REFUND_SUCCESS,
                'third_refund_no' => (string)$thirdRefundNo,
                'notify_count' => intval($refund['notify_count']) + 1,
                'last_notify_at' => $now,
                'refunded_at' => $now,
                'update_time' => $now,
            ]);
            Db::commit();
            return ['idempotent' => false, 'm_ids' => array_values(array_unique($mIds))];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function failRefund($refundNo, $message = '')
    {
        Db::startTrans();
        try {
            $refund = MachineServiceFeeRefundModel::where('refund_no', $refundNo)->lock(true)->find();
            if (!$refund || intval($refund['status']) === self::REFUND_SUCCESS) {
                Db::commit();
                return false;
            }
            $now = time();
            $refund->save([
                'status' => self::REFUND_FAILED,
                'reason' => mb_substr(trim((string)$refund['reason'] . ' ' . (string)$message), 0, 255),
                'update_time' => $now,
            ]);
            MachineServiceFeeOrderModel::where('msfo_id', intval($refund['msfo_id']))
                ->update(['refund_status' => self::REFUND_FAILED, 'update_time' => $now]);
            MachineServiceFeeOrderDetailModel::where('msfo_id', intval($refund['msfo_id']))
                ->update(['refund_status' => self::REFUND_NONE, 'update_time' => $now]);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function addLog(array $data)
    {
        $defaults = [
            'm_id' => 0,
            'machine_id' => '',
            'action' => '',
            'old_fee_cent' => '0.00',
            'new_fee_cent' => '0.00',
            'old_expire_at' => 0,
            'new_expire_at' => 0,
            'order_no' => '',
            'refund_no' => '',
            'operator_id' => 0,
            'remark' => '',
            'create_time' => time(),
        ];
        return MachineServiceFeeLogModel::create(array_merge($defaults, $data));
    }

    public static function makeNo($prefix)
    {
        try {
            $random = strtoupper(bin2hex(random_bytes(4)));
        } catch (\Throwable $e) {
            $random = strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }
        return strtoupper($prefix) . date('YmdHis') . $random;
    }
}
