<?php

namespace app\AppFactory\Kernel\Service\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueOrderModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleCouponModel;
use think\facade\Db;

class RevenueSettlementService
{
    public function handlePaymentSuccess($orderId, $payTime = 0)
    {
        $payTime = intval($payTime) ?: time();
        $records = RevenueOrderModel::where(['order_id' => intval($orderId)])
            ->whereIn('status', [0, 2])
            ->select()
            ->toArray();
        $flag = [1];
        $flag[] = $this->deductCouponUsage(intval($orderId));
        foreach ($records as $record) {
            $flag[] = $this->settleRecord(intval($record['ro_id']), $payTime, false, false);
        }
        return flag_check($flag);
    }

    public function markPaymentFailed($orderId)
    {
        RevenueOrderModel::where(['order_id' => intval($orderId)])
            ->whereIn('status', [0, 2])
            ->update(['status' => 3, 'update_time' => time()]);
        return true;
    }

    public function settleDue($limit = 500, $now = 0)
    {
        $now = intval($now) ?: time();
        $ids = RevenueOrderModel::where(['status' => 2])
            ->where('planned_revenue_time', '<=', $now)
            ->order('planned_revenue_time asc,ro_id asc')
            ->limit(max(1, intval($limit)))
            ->column('ro_id');
        $success = 0;
        $failed = 0;
        foreach ($ids as $roId) {
            if ($this->settleRecord(intval($roId), $now, true, true)) {
                $success++;
            } else {
                $failed++;
            }
        }
        return ['total' => count($ids), 'success' => $success, 'failed' => $failed];
    }

    protected function settleRecord($roId, $payTime, $forceDue = false, $manageTransaction = false)
    {
        if ($manageTransaction) Db::startTrans();
        try {
            $record = RevenueOrderModel::where(['ro_id' => $roId])->lock(true)->find();
            if (!$record || !in_array(intval($record['status'] ?? 0), [0, 2], true)) {
                if ($manageTransaction) Db::commit();
                return true;
            }

            $settlementType = intval($record['settlement_type'] ?? 1) ?: 1;
            $settlementDays = max(0, intval($record['settlement_days'] ?? 0));
            $plannedTime = intval($record['planned_revenue_time'] ?? 0);
            if ($settlementType === 2 && !$plannedTime) {
                $plannedTime = $this->getPlannedRevenueTime($payTime, $settlementDays);
            }

            if ($settlementType === 2 && !$forceDue && $plannedTime > time()) {
                RevenueOrderModel::where(['ro_id' => $roId])->update([
                    'status' => 2,
                    'planned_revenue_time' => $plannedTime,
                    'update_time' => time(),
                ]);
                if ($manageTransaction) Db::commit();
                return true;
            }
            if ($settlementType === 2 && $plannedTime > time()) {
                if ($manageTransaction) Db::commit();
                return true;
            }

            $incomeAmount = is_numeric($record['income_amount'] ?? null) ? (string)$record['income_amount'] : '0';
            $refundAmount = is_numeric($record['refund_amount'] ?? null) ? (string)$record['refund_amount'] : '0';
            $settleableAmount = bcsub($incomeAmount, $refundAmount, 3);
            if (bccomp($settleableAmount, '0', 3) < 0) {
                $settleableAmount = '0';
            }
            if (($record['account_type'] ?? '') === 'balance' && bccomp($settleableAmount, '0', 3) > 0) {
                $result = Db::name('auth_manager')
                    ->where(['manager_id' => intval($record['manager_id'] ?? 0)])
                    ->inc('balance', $settleableAmount)
                    ->update();
                if (!$result) {
                    throw new \Exception("增加分账账户管理人余额失败");
                }
            }
            RevenueOrderModel::where(['ro_id' => $roId])->update([
                'status' => 1,
                'planned_revenue_time' => $plannedTime ?: null,
                'revenue_time' => time(),
                'update_time' => time(),
            ]);
            if ($manageTransaction) Db::commit();
            return true;
        } catch (\Exception $e) {
            if ($manageTransaction) Db::rollback();
            actionException($e, 1);
            return false;
        }
    }

    protected function deductCouponUsage($orderId)
    {
        $records = RevenueOrderModel::where(['order_id' => intval($orderId), 'rule_mode' => 5, 'coupon_use_deducted' => 0])
            ->whereIn('status', [0, 2])
            ->field('rrc_id')
            ->group('rrc_id')
            ->select()
            ->toArray();
        if (!$records) {
            return true;
        }

        foreach ($records as $record) {
            $rrcId = intval($record['rrc_id'] ?? 0);
            if ($rrcId <= 0) {
                continue;
            }
            $coupon = RevenueRuleCouponModel::where(['rrc_id' => $rrcId])->lock(true)->find();
            if (!$coupon) {
                throw new \Exception("优惠券分账配置不存在");
            }
            $remainCount = intval($coupon['remain_count'] ?? 0);
            $expireTime = intval($coupon['expire_time'] ?? 0);
            if ($remainCount <= 0) {
                throw new \Exception("优惠券分账剩余次数不足");
            }
            if ($expireTime > 0 && $expireTime <= time()) {
                throw new \Exception("优惠券分账已过期");
            }
            $newRemainCount = $remainCount - 1;
            $update = [
                'used_count' => intval($coupon['used_count'] ?? 0) + 1,
                'remain_count' => $newRemainCount,
                'update_time' => time(),
            ];
            if ($newRemainCount <= 0) {
                $update['status'] = 2;
            }
            RevenueRuleCouponModel::where(['rrc_id' => $rrcId])->update($update);
            RevenueOrderModel::where(['order_id' => intval($orderId), 'rule_mode' => 5, 'rrc_id' => $rrcId, 'coupon_use_deducted' => 0])
                ->whereIn('status', [0, 2])
                ->update([
                    'coupon_use_count_before' => $remainCount,
                    'coupon_use_count_after' => $newRemainCount,
                    'coupon_use_deducted' => 1,
                    'update_time' => time(),
                ]);
        }

        return true;
    }

    protected function getPlannedRevenueTime($payTime, $settlementDays)
    {
        $dayStart = strtotime(date('Y-m-d 00:00:00', intval($payTime) ?: time()));
        return strtotime('+' . max(1, intval($settlementDays)) . ' day', $dayStart);
    }
}
