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
        $fallbackAmount = self::money(bcsub(self::money($order['total_price'] ?? 0), self::money($rentalAmount), 2));
        $matchedDetails = [];
        $scopeType = '';
        $scopeIds = [];

        foreach ($scopes as $scope) {
            $scopeMId = intval($scope['m_id'] ?? 0);
            $scopeGId = intval($scope['g_id'] ?? 0);
            if ($scopeMId > 0 && $scopeGId <= 0) {
                if ($scopeMId === $mId) {
                    self::appendMatchedScopeDetails($matchedDetails, $details, $rentalAmountsBySod, 0, 0);
                    $scopeType = 'machine';
                    $scopeIds[] = intval($scope['rrcs_id']);
                }
                continue;
            }
            if ($scopeMId <= 0 && $scopeGId > 0) {
                $beforeCount = count($matchedDetails);
                self::appendMatchedScopeDetails($matchedDetails, $details, $rentalAmountsBySod, $scopeGId, 0);
                if (count($matchedDetails) > $beforeCount) {
                    $scopeType = $scopeType ?: 'goods';
                    $scopeIds[] = intval($scope['rrcs_id']);
                }
                continue;
            }
            if ($scopeMId > 0 && $scopeGId > 0) {
                if ($scopeMId !== $mId) {
                    continue;
                }
                $beforeCount = count($matchedDetails);
                self::appendMatchedScopeDetails($matchedDetails, $details, $rentalAmountsBySod, $scopeGId, intval($scope['mg_id'] ?? 0));
                if (count($matchedDetails) > $beforeCount) {
                    $scopeType = $scopeType ?: 'machine_goods';
                    $scopeIds[] = intval($scope['rrcs_id']);
                }
            }
        }

        $matchedAmount = self::sumMatchedDetailAmount($matchedDetails);
        if (!$matchedDetails && $scopeType === 'machine') {
            $matchedAmount = $fallbackAmount;
        }

        return [
            'matched' => bccomp($matchedAmount, '0.01', 2) >= 0,
            'base_amount' => self::money($matchedAmount),
            'scope_type' => $scopeType,
            'scope_ids' => $scopeIds,
            'details' => array_values($matchedDetails),
        ];
    }

    public static function normalizeDetails($details)
    {
        if (is_object($details) && method_exists($details, 'toArray')) {
            $details = $details->toArray();
        }
        return is_array($details) ? $details : [];
    }

    protected static function appendMatchedScopeDetails(array &$matchedDetails, array $details, array $rentalAmountsBySod, $gId = 0, $mgId = 0)
    {
        foreach ($details as $index => $detail) {
            if (intval($gId) > 0 && intval($detail['g_id'] ?? 0) !== intval($gId)) {
                continue;
            }
            if (intval($mgId) > 0 && intval($detail['mg_id'] ?? 0) !== intval($mgId)) {
                continue;
            }
            $sodId = intval($detail['sod_id'] ?? 0);
            $detailAmount = self::money($detail['total_sod_price'] ?? 0);
            $baseAmount = self::money(bcsub($detailAmount, $rentalAmountsBySod[$sodId] ?? '0', 2));
            if (bccomp($baseAmount, '0.01', 2) >= 0) {
                $key = $sodId > 0 ? 'sod_' . $sodId : 'idx_' . $index;
                $detail['_scope_amount'] = $baseAmount;
                $matchedDetails[$key] = $detail;
            }
        }
    }

    protected static function sumMatchedDetailAmount(array $details)
    {
        $amount = '0.00';
        foreach ($details as $detail) {
            $amount = bcadd($amount, self::money($detail['_scope_amount'] ?? ($detail['total_sod_price'] ?? 0)), 2);
        }
        return self::money($amount);
    }

    public static function calculateOrderDiscountAmount(array $coupon, $baseAmount)
    {
        $discountType = intval($coupon['discount_type'] ?? 0);
        $discountValue = self::money($coupon['discount_value'] ?? 0, 3);
        $baseAmount = self::money($baseAmount);
        if ($discountType === 1) {
            $discount = self::money($discountValue);
        } elseif ($discountType === 2) {
            $discount = self::money(bcmul($baseAmount, bcdiv($discountValue, '100', 6), 6));
        } else {
            return '0.00';
        }
        if (bccomp($discount, $baseAmount, 2) > 0) {
            $discount = $baseAmount;
        }
        return self::money($discount);
    }

    protected static function money($value, $scale = 2)
    {
        if ($value === null || $value === '') {
            $value = 0;
        }
        return bcadd((string)$value, '0', $scale);
    }
}
