<?php

namespace app\AppFactory\Kernel\Service\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueOrderModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;
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
        $records = RevenueOrderModel::where(['order_id' => intval($orderId), 'rule_mode' => 5, 'status' => 0])
            ->field('rrcfg_id')
            ->group('rrcfg_id')
            ->select()
            ->toArray();
        if (!$records) {
            return true;
        }

        foreach ($records as $record) {
            $rrcfgId = intval($record['rrcfg_id'] ?? 0);
            if ($rrcfgId <= 0) {
                continue;
            }
            $config = RevenueRuleConfigModel::where(['rrcfg_id' => $rrcfgId, 'rule_mode' => 5])->lock(true)->find();
            if (!$config) {
                throw new \Exception("优惠券分账配置不存在");
            }
            if (!is_array($config)) $config = $config->toArray();
            $this->deductActivityCouponUsage($orderId, intval($config['coupon_id'] ?? 0));
        }

        return true;
    }

    protected function deductActivityCouponUsage($orderId, $couponId)
    {
        if ($couponId <= 0) throw new \Exception("优惠券分账未关联活动优惠券");
        $order = Db::name('sale_orders')
            ->where(['order_id' => intval($orderId)])
            ->field('order_id,trade_no,m_id,machine_id,machine_name,total_price,retail_price,discount_price,revenue_coupon_code')
            ->find();
        if (!$order || empty($order['revenue_coupon_code'])) {
            throw new \Exception("订单未绑定分账优惠券编码");
        }
        if (ActivityCouponUsedModel::where(['order_id' => intval($orderId), 'c_id' => $couponId, 'status' => 2])->find()) {
            return true;
        }

        $coupon = ActivityCouponModel::where(['c_id' => $couponId])->lock(true)->find();
        if (!$coupon) throw new \Exception("关联活动优惠券不存在");
        if (!is_array($coupon)) $coupon = $coupon->toArray();
        $code = trim(strval($order['revenue_coupon_code']));
        $used = ActivityCouponUsedModel::where(['c_id' => $couponId, 'code' => $code])->lock(true)->find();
        $used = $used && !is_array($used) ? $used->toArray() : $used;
        if (!$used && !empty($coupon['code']) && trim(strval($coupon['code'])) !== $code) {
            throw new \Exception("订单分账优惠券编码与活动优惠券不匹配");
        }
        if ($used && intval($used['status'] ?? 0) === 2 && intval($used['order_id'] ?? 0) !== intval($orderId)) {
            // 分账优惠券允许多个订单复用同一券码，不受单订单独占限制
            // 由 activity_coupon.used_limit 字段控制总使用次数上限
            $used = null;
        }

        $usedData = [
            'order_id' => intval($orderId),
            'trade_no' => $order['trade_no'] ?? '',
            'm_id' => intval($order['m_id'] ?? 0),
            'machine_id' => $order['machine_id'] ?? '',
            'machine_name' => $order['machine_name'] ?? '',
            'original_price' => $order['retail_price'] ?: $order['total_price'],
            'discount_price' => $order['discount_price'] ?? 0,
            'retail_price' => $order['total_price'] ?? 0,
            'status' => 2,
            'used_time' => time(),
            'used_date' => strtotime(date('Y-m-d')),
        ];
        if ($used) {
            $usedData['cu_id'] = intval($used['cu_id']);
            ActivityCouponUsedModel::update($usedData);
        } else {
            $usedData['c_id'] = $couponId;
            $usedData['c_type'] = intval($coupon['c_type'] ?? 0);
            $usedData['pay_limit'] = $coupon['pay_limit'] ?? 0;
            $usedData['reduction'] = $coupon['reduction'] ?? 0;
            $usedData['code'] = $code;
            $usedData['code_type'] = 2;
            ActivityCouponUsedModel::create($usedData);
        }

        if (!empty($coupon['code']) && intval($coupon['used_limit'] ?? 0) > 0) {
            $usedCount = ActivityCouponUsedModel::where(['c_id' => $couponId, 'status' => 2])->count();
            if ($usedCount >= intval($coupon['used_limit'])) {
                ActivityCouponModel::where(['c_id' => $couponId])->update(['status' => 3, 'update_time' => time()]);
            }
        }
        return true;
    }

    protected function getPlannedRevenueTime($payTime, $settlementDays)
    {
        $dayStart = strtotime(date('Y-m-d 00:00:00', intval($payTime) ?: time()));
        return strtotime('+' . max(1, intval($settlementDays)) . ' day', $dayStart);
    }
}
