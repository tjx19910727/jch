<?php

namespace app\AppFactory\Kernel\Service\Revenue;

use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigScopeModel;

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

    public static function existsRevenueCouponCode($couponCode, $excludeConfigId = 0)
    {
        $coupon = self::findActivityCouponByCode($couponCode);
        if (!$coupon) return false;
        $query = RevenueRuleConfigModel::where(['coupon_id' => intval($coupon['c_id']), 'rule_mode' => 5]);
        if (intval($excludeConfigId) > 0) {
            $query->where('rrcfg_id', '<>', intval($excludeConfigId));
        }
        return $query->find() ? true : false;
    }

    public static function isCouponCodeUnique($couponCode, $excludeConfigId = 0)
    {
        $couponCode = trim(strval($couponCode));
        if ($couponCode === '') return false;
        if (self::existsRevenueCouponCode($couponCode, $excludeConfigId)) {
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

    public static function findEnabledCouponByCode($couponCode, $field = '*')
    {
        $activityCoupon = self::findActivityCouponByCode($couponCode);
        if (!$activityCoupon) {
            return null;
        }
        $config = RevenueRuleConfigModel::where([
                'coupon_id' => intval($activityCoupon['c_id']),
                'status' => 1,
                'rule_mode' => 5,
            ])->find();
        if (!$config) {
            return null;
        }
        if (!is_array($config)) {
            $config = $config->toArray();
        }
        $config['rr_id'] = intval($config['rrcfg_id']);
        return self::mergeActivityCouponData($config, $activityCoupon, $couponCode);
    }

    public static function checkUsable(array $coupon)
    {
        $status = intval($coupon['activity_status'] ?? $coupon['status'] ?? 0);
        if (!in_array($status, [1, 2], true)) {
            return [
                'usable' => false,
                'message' => '优惠券不可用',
                'reason' => 'status_invalid',
            ];
        }
        $startDate = intval($coupon['start_date'] ?? 0);
        if ($startDate > 0 && $startDate > time()) {
            return [
                'usable' => false,
                'message' => '优惠券未开始',
                'reason' => 'not_begin',
            ];
        }
        $endDate = intval($coupon['end_date'] ?? 0);
        if ($endDate > 0 && $endDate <= time()) {
            return [
                'usable' => false,
                'message' => '优惠券已过期',
                'reason' => 'expired',
            ];
        }
        if (intval($coupon['code_type'] ?? 2) === 1) {
            $usedStatus = intval($coupon['used_status'] ?? 0);
            if ($usedStatus === 2) return ['usable' => false, 'message' => '优惠券已使用', 'reason' => 'used'];
            if ($usedStatus === 3) return ['usable' => false, 'message' => '优惠券已过期', 'reason' => 'used_expired'];
            if ($usedStatus === 4) return ['usable' => false, 'message' => '优惠券已作废', 'reason' => 'used_cancelled'];
        } else {
            $usedLimit = intval($coupon['used_limit'] ?? 0);
            if ($usedLimit > 0 && intval($coupon['used_count'] ?? 0) >= $usedLimit) {
                return ['usable' => false, 'message' => '优惠券已无可用次数', 'reason' => 'used_limit'];
            }
        }
        return [
            'usable' => true,
            'message' => '',
            'reason' => '',
        ];
    }

    public static function matchScope(array $coupon, array $order, array $details, array $rentalAmountsBySod = [], $rentalAmount = '0')
    {
        $scopes = RevenueRuleConfigScopeModel::where(['rrcfg_id' => intval($coupon['rr_id'] ?? $coupon['rrcfg_id'] ?? 0), 'status' => 1])
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
            if ($scopeMId <= 0 && $scopeGId <= 0 && intval($scope['mg_id'] ?? 0) <= 0) {
                self::appendMatchedScopeDetails($matchedDetails, $details, $rentalAmountsBySod, 0, 0);
                $scopeType = 'all';
                $scopeIds[] = intval($scope['rrcs_id']);
                continue;
            }
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

    public static function findActivityCouponByCode($couponCode)
    {
        $couponCode = trim(strval($couponCode));
        if ($couponCode === '') return null;
        $field = 'c_id,code,c_type,pay_limit,reduction,status,start_date,end_date,used_limit,designated_machine,designated_goods,exclusion,creator';
        $coupon = ActivityCouponModel::where(['code' => $couponCode])->field($field)->order('c_id desc')->find();
        $used = null;
        if (!$coupon) {
            $used = ActivityCouponUsedModel::where(['code' => $couponCode, 'code_type' => 1])
                ->field('cu_id,c_id,code,c_type,pay_limit,reduction,status,code_type')
                ->order('cu_id desc')
                ->find();
            if (!$used) return null;
            if (!is_array($used)) $used = $used->toArray();
            $coupon = ActivityCouponModel::where(['c_id' => intval($used['c_id'])])->field($field)->find();
        }
        if (!$coupon) return null;
        if (!is_array($coupon)) $coupon = $coupon->toArray();
        $coupon['coupon_code'] = $couponCode;
        $coupon['code_type'] = $used ? 1 : 2;
        $coupon['used_count'] = $used ? 0 : ActivityCouponUsedModel::where(['c_id' => intval($coupon['c_id']), 'status' => 2])->count();
        if ($used) {
            $coupon['cu_id'] = intval($used['cu_id'] ?? 0);
            $coupon['used_status'] = intval($used['status'] ?? 0);
        }
        return $coupon;
    }

    protected static function mergeActivityCouponData(array $config, array $activityCoupon, $couponCode)
    {
        $cType = intval($activityCoupon['c_type'] ?? 0);
        $reduction = self::money($activityCoupon['reduction'] ?? 0, 3);
        $discountType = $cType === 1 ? 1 : ($cType === 2 ? 2 : 0);
        $discountValue = $discountType === 2
            ? self::money(bcsub('100', $reduction, 3), 3)
            : $reduction;
        return array_merge($config, [
            'coupon_id' => intval($activityCoupon['c_id']),
            'coupon_code' => trim(strval($couponCode)),
            'activity_status' => intval($activityCoupon['status'] ?? 0),
            'start_date' => intval($activityCoupon['start_date'] ?? 0),
            'end_date' => intval($activityCoupon['end_date'] ?? 0),
            'used_limit' => intval($activityCoupon['used_limit'] ?? 0),
            'used_count' => intval($activityCoupon['used_count'] ?? 0),
            'code_type' => intval($activityCoupon['code_type'] ?? 2),
            'cu_id' => intval($activityCoupon['cu_id'] ?? 0),
            'used_status' => intval($activityCoupon['used_status'] ?? 0),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'activity_coupon' => $activityCoupon,
        ]);
    }

    protected static function money($value, $scale = 2)
    {
        if ($value === null || $value === '') {
            $value = 0;
        }
        return bcadd((string)$value, '0', $scale);
    }
}
