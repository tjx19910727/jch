<?php

namespace app\AppFactory\Kernel\Service\Revenue;

use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleCouponModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleCouponScopeModel;

class RevenueCouponService
{
    public static function hasActivityCouponCode($couponCode)
    {
        $couponCode = trim(strval($couponCode));
        if ($couponCode === '') return false;
        if (ActivityCouponModel::where(['code' => $couponCode])->find()) {
            return true;
        }
        return ActivityCouponUsedModel::where(['code' => $couponCode])->find() ? true : false;
    }

    public static function existsRevenueCouponCode($couponCode, $excludeRrcId = 0)
    {
        $query = RevenueRuleCouponModel::where(['coupon_code' => trim(strval($couponCode))]);
        if (intval($excludeRrcId) > 0) {
            $query->where('rrc_id', '<>', intval($excludeRrcId));
        }
        return $query->find() ? true : false;
    }

    public static function isCouponCodeUnique($couponCode, $excludeRrcId = 0)
    {
        $couponCode = trim(strval($couponCode));
        if ($couponCode === '') return false;
        if (self::existsRevenueCouponCode($couponCode, $excludeRrcId)) {
            return false;
        }
        return self::hasActivityCouponCode($couponCode) ? false : true;
    }

    public static function generateUniqueCouponCode($maxAttempts = 100)
    {
        $maxAttempts = max(1, intval($maxAttempts));
        for ($i = 0; $i < $maxAttempts; $i++) {
            $couponCode = strval(mt_rand(100000, 999999));
            if (self::isCouponCodeUnique($couponCode)) {
                return $couponCode;
            }
        }
        throw new \Exception("生成唯一优惠券编码失败，请稍后重试");
    }

    public static function findEnabledCouponByCode($couponCode, $field = 'rrc.*,rr.rule_name,rr.rule_mode,rr.settlement_type,rr.settlement_days')
    {
        return RevenueRuleCouponModel::alias('rrc')
            ->join('revenue_rule rr', 'rr.rr_id = rrc.rr_id')
            ->where([
                'rrc.coupon_code' => trim(strval($couponCode)),
                'rrc.status' => 1,
                'rr.status' => 1,
                'rr.rule_mode' => 5,
            ])
            ->field($field)
            ->find();
    }

    public static function checkUsable(array $coupon)
    {
        $remainCount = intval($coupon['remain_count'] ?? 0);
        if ($remainCount <= 0) {
            return [
                'usable' => false,
                'message' => '优惠券已无可用次数',
                'reason' => 'remain_count_empty',
            ];
        }
        $expireTime = intval($coupon['expire_time'] ?? 0);
        if ($expireTime > 0 && $expireTime <= time()) {
            return [
                'usable' => false,
                'message' => '优惠券已过期',
                'reason' => 'expired',
            ];
        }
        return [
            'usable' => true,
            'message' => '',
            'reason' => '',
        ];
    }

    public static function matchScope(array $coupon, array $order, array $details, array $rentalAmountsBySod = [], $rentalAmount = '0')
    {
        $scopes = RevenueRuleCouponScopeModel::where(['rrc_id' => intval($coupon['rrc_id']), 'status' => 1])
            ->order('rrcs_id asc')
            ->select()
            ->toArray();
        $details = self::normalizeDetails($details);
        $mId = intval($order['m_id'] ?? 0);
        $orderAmount = self::money(bcsub(self::money($order['total_price'] ?? 0), self::money($rentalAmount), 2));
        $matchedAmount = '0.00';
        $scopeType = '';
        $scopeIds = [];

        foreach ($scopes as $scope) {
            $scopeMId = intval($scope['m_id'] ?? 0);
            $scopeGId = intval($scope['g_id'] ?? 0);
            if ($scopeMId > 0 && $scopeGId <= 0) {
                if ($scopeMId === $mId) {
                    $matchedAmount = $orderAmount;
                    $scopeType = 'machine';
                    $scopeIds[] = intval($scope['rrcs_id']);
                }
                continue;
            }
            if ($scopeMId <= 0 && $scopeGId > 0) {
                $amount = self::sumScopeGoodsAmount($details, $rentalAmountsBySod, $scopeGId, 0);
                if (bccomp($amount, '0.01', 2) >= 0) {
                    $matchedAmount = bcadd($matchedAmount, $amount, 2);
                    $scopeType = $scopeType ?: 'goods';
                    $scopeIds[] = intval($scope['rrcs_id']);
                }
                continue;
            }
            if ($scopeMId > 0 && $scopeGId > 0) {
                if ($scopeMId !== $mId) {
                    continue;
                }
                $amount = self::sumScopeGoodsAmount($details, $rentalAmountsBySod, $scopeGId, intval($scope['mg_id'] ?? 0));
                if (bccomp($amount, '0.01', 2) >= 0) {
                    $matchedAmount = bcadd($matchedAmount, $amount, 2);
                    $scopeType = $scopeType ?: 'machine_goods';
                    $scopeIds[] = intval($scope['rrcs_id']);
                }
            }
        }

        return [
            'matched' => bccomp($matchedAmount, '0.01', 2) >= 0,
            'base_amount' => self::money($matchedAmount),
            'scope_type' => $scopeType,
            'scope_ids' => $scopeIds,
        ];
    }

    public static function normalizeDetails($details)
    {
        if (is_object($details) && method_exists($details, 'toArray')) {
            $details = $details->toArray();
        }
        return is_array($details) ? $details : [];
    }

    protected static function sumScopeGoodsAmount(array $details, array $rentalAmountsBySod, $gId, $mgId = 0)
    {
        $amount = '0.00';
        foreach ($details as $detail) {
            if (intval($detail['g_id'] ?? 0) !== intval($gId)) {
                continue;
            }
            if (intval($mgId) > 0 && intval($detail['mg_id'] ?? 0) !== intval($mgId)) {
                continue;
            }
            $sodId = intval($detail['sod_id'] ?? 0);
            $detailAmount = self::money($detail['total_sod_price'] ?? 0);
            $baseAmount = self::money(bcsub($detailAmount, $rentalAmountsBySod[$sodId] ?? '0', 2));
            if (bccomp($baseAmount, '0.01', 2) >= 0) {
                $amount = bcadd($amount, $baseAmount, 2);
            }
        }
        return self::money($amount);
    }

    protected static function money($value, $scale = 2)
    {
        if ($value === null || $value === '') {
            $value = 0;
        }
        return bcadd((string)$value, '0', $scale);
    }
}
