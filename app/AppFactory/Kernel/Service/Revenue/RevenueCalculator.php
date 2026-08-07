<?php

namespace app\AppFactory\Kernel\Service\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueOrderModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueAccountModel;
use app\AppFactory\Kernel\Model\Revenue\RevenuePayChannelModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigScopeModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDetailsModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use think\facade\Db;

class RevenueCalculator
{
    protected $order = [];
    protected $details = [];
    protected $records = [];
    protected $rentalAmount = '0';
    protected $productAmount = '0';
    protected $couponAmount = '0';
    protected $rentalAmountsBySod = [];
    protected $revenuePayChannel = [];

    public function calculate(array $order)
    {
        $this->order = $order;
        $this->records = [];
        $this->rentalAmount = '0';
        $this->productAmount = '0';
        $this->couponAmount = '0';
        $this->rentalAmountsBySod = [];
        $this->revenuePayChannel = [];
        if (!$this->shouldCalculateRevenue()) {
            $this->clearPendingRecords();
            return true;
        }
        $this->details = $order['details'] ?? $order['detail'] ?? [];
        if (!$this->details && !empty($order['order_id'])) {
            $this->details = SaleOrdersDetailsModel::where(['order_id' => $order['order_id']])->select()->toArray();
        }
        if (!$this->details) {
            return true;
        }

        $this->calculateRental();
        $hasCouponCode = $this->hasRevenueCouponCode();
        $hasCouponRule = $this->calculateCouponRule();
        $hasProductRule = $hasCouponCode ? false : $this->calculateProductRule();
        if (!$hasCouponCode && !$hasProductRule) {
            $this->calculateBaseRule(false);
        } elseif ($hasCouponRule || $hasProductRule) {
            $this->calculateBaseRule(true);
        }

        return $this->saveRecords();
    }

    protected function calculateRental()
    {
        $payerAoId = intval($this->order['ao_id'] ?? 0);
        $matchedRuleCount = 0;
        foreach ($this->details as $detail) {
            $rentalRule = $this->getRuleByMode(2, $detail);
            if (!$rentalRule) {
                continue;
            }
            $matchedRuleCount++;
            $receiverAoId = intval($detail['sod_ao_id'] ?? 0);
            if ($receiverAoId <= 0 || $receiverAoId === $payerAoId) {
                continue;
            }
            $item = $this->getRentalRuleItemFromRule($rentalRule, $receiverAoId);
            $this->logRevenueConfig('设备出租规则明细查询', [
                'rr_id' => intval($rentalRule['rr_id']),
                'sod_id' => intval($detail['sod_id'] ?? 0),
                'receiver_ao_id' => $receiverAoId,
                'found_item' => $item ? 1 : 0,
                'item_key' => $item ? intval($item['item_key']) : 0,
            ]);
            if (!$item) {
                continue;
            }
            $amount = $this->money($detail['total_sod_price'] ?? 0);
            if (bccomp($amount, '0.01', 2) < 0) {
                continue;
            }
            $account = $this->getAccountById(intval($item['ra_id']));
            if (!$account) {
                $this->logRevenueConfig('分账账户查询', [
                    'source' => 'rental',
                    'ra_id' => intval($item['ra_id']),
                    'found_account' => 0,
                ]);
                continue;
            }
            $calc = $this->calculateRuleItemAmount($item, $amount, '0');
            if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) {
                continue;
            }
            if (bccomp($calc['income_amount'], $amount, 2) > 0) {
                throw new \Exception("商品组织{$receiverAoId}设备出租分账金额不能超过商品金额");
            }
            $this->rentalAmount = bcadd($this->rentalAmount, $calc['income_amount'], 2);
            $sodId = intval($detail['sod_id'] ?? 0);
            $this->rentalAmountsBySod[$sodId] = bcadd($this->rentalAmountsBySod[$sodId] ?? '0', $calc['income_amount'], 2);
            $this->records[] = $this->buildRecord([
                'sod_id' => $detail['sod_id'] ?? 0,
                'sod_amount' => $detail['retail_price'] ?? 0,
                'sod_quantity' => $detail['quantity'] ?? 0,
                'sod_total_price' => $amount,
                'rule_mode' => 2,
                'rrcfg_id' => $rentalRule['rr_id'],
                'payer_ao_id' => $payerAoId,
                'receiver_ao_id' => $receiverAoId,
                'calc_type' => intval($item['calc_type']),
                'income_value' => $calc['income_value'],
                'income_amount' => $calc['income_amount'],
                'source' => 'rental',
            ], $account, $rentalRule);
        }
        if ($matchedRuleCount === 0) {
            $this->logRevenueConfig('设备出租规则明细查询', [
                'found_rule' => 0,
            ]);
        }
    }

    protected function calculateProductRule()
    {
        $hasMatchedRuleItem = false;
        foreach ($this->details as $detail) {
            $rule = $this->getRuleByMode(4, $detail);
            if (!$rule) {
                continue;
            }
            $items = $this->getRuleItems($rule);
            $this->logRevenueConfig('设备商品分账规则明细查询', [
                'rr_id' => intval($rule['rr_id']),
                'item_count' => count($items),
                'g_ids' => $this->collectColumnValues($items, 'g_id'),
            ]);
            if (!$items) {
                continue;
            }
            $gId = intval($detail['g_id'] ?? 0);
            $sodId = intval($detail['sod_id'] ?? 0);
            $detailAmount = $this->money($detail['total_sod_price'] ?? 0);
            $baseAmount = $this->money(bcsub($detailAmount, $this->rentalAmountsBySod[$sodId] ?? '0', 2));
            if ($gId <= 0 || bccomp($baseAmount, '0.01', 2) < 0) {
                continue;
            }
            $detailAllocatedAmount = '0.00';
            $matchedItemCount = 0;
            $matchedItemKeys = [];
            foreach ($items as $item) {
                if (intval($item['g_id'] ?? 0) > 0 && intval($item['g_id'] ?? 0) !== $gId) continue;
                $hasMatchedRuleItem = true;
                $matchedItemCount++;
                $matchedItemKeys[] = intval($item['item_key']);
                $account = $this->getAccountById(intval($item['ra_id']));
                if (!$account) {
                    $this->logRevenueConfig('分账账户查询', [
                        'source' => 'product_rule',
                        'ra_id' => intval($item['ra_id']),
                        'item_key' => intval($item['item_key']),
                        'g_id' => $gId,
                        'found_account' => 0,
                    ]);
                    continue;
                }
                $calc = $this->calculateProductRuleItemAmount($item, $baseAmount, intval($detail['quantity'] ?? 0));
                if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) continue;
                $detailAllocatedAmount = bcadd($detailAllocatedAmount, $calc['income_amount'], 2);
                if (bccomp($detailAllocatedAmount, $baseAmount, 2) > 0) {
                    throw new \Exception("商品{$gId}设备商品分账金额不能超过商品可分金额");
                }
                $this->productAmount = bcadd($this->productAmount, $calc['income_amount'], 2);
                $this->records[] = $this->buildRecord([
                    'sod_id' => $sodId,
                    'g_id' => $gId,
                    'mg_id' => intval($detail['mg_id'] ?? 0),
                    'sod_amount' => $detail['retail_price'] ?? 0,
                    'sod_quantity' => intval($detail['quantity'] ?? 0),
                    'sod_total_price' => $detailAmount,
                    'rule_mode' => 4,
                    'rrcfg_id' => $rule['rr_id'],
                    'payer_ao_id' => intval($this->order['ao_id'] ?? 0),
                    'receiver_ao_id' => intval($item['receiver_ao_id']),
                    'calc_type' => intval($item['calc_type']),
                    'income_value' => $calc['income_value'],
                    'income_amount' => $calc['income_amount'],
                    'source' => 'product_rule',
                ], $account, $rule);
            }
            $this->logRevenueConfig('设备商品分账商品匹配', [
                'rr_id' => intval($rule['rr_id']),
                'sod_id' => $sodId,
                'g_id' => $gId,
                'mg_id' => intval($detail['mg_id'] ?? 0),
                'base_amount' => $baseAmount,
                'matched_item_count' => $matchedItemCount,
                'matched_item_keys' => $matchedItemKeys,
            ]);
        }
        return $hasMatchedRuleItem;
    }

    protected function calculateCouponRule()
    {
        $couponCode = trim($this->order['revenue_coupon_code'] ?? '');
        if ($couponCode === '') {
            $this->logRevenueConfig('优惠券分账编码查询', [
                'found_code' => 0,
            ]);
            return false;
        }
        if (!preg_match('/^[1-9][0-9]{5}$/', $couponCode)) {
            $this->logRevenueConfig('优惠券分账编码查询', [
                'coupon_code' => $couponCode,
                'found_code' => 0,
                'skip_reason' => 'invalid_coupon_code',
            ]);
            return false;
        }

        $coupon = RevenueCouponService::findEnabledCouponByCode($couponCode, intval($this->order['pay_type'] ?? 0));
        if ($coupon && !is_array($coupon)) {
            $coupon = $coupon->toArray();
        }
        $this->logRevenueConfig('优惠券分账编码查询', [
            'coupon_code' => $couponCode,
            'found_coupon' => $coupon ? 1 : 0,
            'rr_id' => $coupon ? intval($coupon['rr_id']) : 0,
        ]);
        if (!$coupon) {
            return false;
        }

        $usable = RevenueCouponService::checkUsable($coupon);
        $this->logRevenueConfig('优惠券分账可用性校验', [
            'rr_id' => intval($coupon['rr_id'] ?? 0),
            'coupon_id' => intval($coupon['coupon_id'] ?? 0),
            'coupon_code' => $coupon['coupon_code'] ?? '',
            'usable' => $usable['usable'] ? 1 : 0,
            'used_limit' => intval($coupon['used_limit'] ?? 0),
            'used_count' => intval($coupon['used_count'] ?? 0),
            'end_date' => intval($coupon['end_date'] ?? 0),
            'skip_reason' => $usable['reason'],
        ]);
        if (!$usable['usable']) {
            return false;
        }

        $matched = RevenueCouponService::matchScope($coupon, $this->order, $this->details, $this->rentalAmountsBySod, $this->rentalAmount);
        $this->logRevenueConfig('优惠券分账范围匹配', [
            'coupon_code' => $couponCode,
            'rr_id' => intval($coupon['rr_id']),
            'matched' => $matched['matched'] ? 1 : 0,
            'scope_type' => $matched['scope_type'],
            'base_amount' => $matched['base_amount'],
            'matched_scope_ids' => $matched['scope_ids'],
        ]);
        if (!$matched['matched'] || bccomp($matched['base_amount'], '0.01', 2) < 0) {
            return false;
        }

        $items = $this->getRuleItems($coupon);
        $this->logRevenueConfig('优惠券分账规则明细查询', [
            'rr_id' => intval($coupon['rr_id']),
            'item_count' => count($items),
            'item_keys' => $this->collectColumnValues($items, 'item_key'),
        ]);
        if (!$items) {
            return false;
        }

        $allocatedAmount = '0.00';
        $hasRecord = false;
        foreach ($items as $item) {
            $account = $this->getAccountById(intval($item['ra_id']));
            if (!$account) {
                $this->logRevenueConfig('分账账户查询', [
                    'source' => 'coupon_rule',
                    'ra_id' => intval($item['ra_id']),
                    'item_key' => intval($item['item_key']),
                    'found_account' => 0,
                ]);
                continue;
            }
            $calc = $this->calculateCouponRuleItemAmount($item, $matched['base_amount']);
            $calc = $this->applyCouponCostAssume($calc, $coupon);
            if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) {
                continue;
            }
            $allocatedAmount = bcadd($allocatedAmount, $calc['income_amount'], 2);
            if (bccomp($allocatedAmount, $matched['base_amount'], 2) > 0) {
                throw new \Exception("优惠券分账金额不能超过优惠券可分金额");
            }
            $this->couponAmount = bcadd($this->couponAmount, $calc['income_amount'], 2);
            $hasRecord = true;
            $this->records[] = $this->buildRecord([
                'rule_mode' => 5,
                'rrcfg_id' => intval($coupon['rr_id']),
                'payer_ao_id' => intval($this->order['ao_id'] ?? 0),
                'receiver_ao_id' => intval($item['receiver_ao_id']),
                'calc_type' => intval($item['calc_type']),
                'income_value' => $calc['income_value'],
                'income_amount' => $calc['income_amount'],
                'source' => 'coupon_rule',
            ], $account, $coupon);
        }
        return $hasRecord;
    }

    protected function hasRevenueCouponCode()
    {
        return trim($this->order['revenue_coupon_code'] ?? '') !== '';
    }

    protected function calculateBaseRule($normalOnly = false)
    {
        $rule = $this->getBaseRuleByMode(1);
        if (!$rule && !$normalOnly) {
            $rule = $this->getBaseRuleByMode(3);
        }
        if (!$rule) {
            return false;
        }
        $items = $this->getRuleItems($rule);
        $ruleMode = intval($rule['rule_mode'] ?? 1);
        $isLegacyDeviceRule = $ruleMode === 3;
        $this->logRevenueConfig($isLegacyDeviceRule ? '设备分账规则明细查询' : '基础分账规则明细查询', [
            'rr_id' => intval($rule['rr_id']),
            'rule_mode' => $ruleMode,
            'item_count' => count($items),
            'item_keys' => $this->collectColumnValues($items, 'item_key'),
        ]);
        if (!$items) {
            return false;
        }

        $allocatedAmount = bcadd(bcadd($this->rentalAmount, $this->couponAmount, 2), $this->productAmount, 2);
        $baseAmount = $this->getBaseRuleAmount($rule, $items, $allocatedAmount);
        if (bccomp($baseAmount, '0.01', 2) < 0) {
            return true;
        }

        $periodKey = date('Y-m', intval($this->order['pay_time'] ?? 0) ?: time());
        $periodBefore = $this->getMachineMonthlyTurnover(intval($this->order['m_id'] ?? 0), $periodKey, intval($rule['turnover_type'] ?? 1));
        $periodAfter = bcadd($periodBefore, $baseAmount, 2);
        $baseAllocatedAmount = '0.00';
        $this->logRevenueConfig($isLegacyDeviceRule ? '设备分账计算基数' : '基础分账计算基数', [
            'rr_id' => intval($rule['rr_id']),
            'rule_mode' => $ruleMode,
            'base_amount' => $baseAmount,
            'allocated_amount' => $allocatedAmount,
            'period_key' => $periodKey,
            'period_amount_before' => $periodBefore,
            'period_amount_after' => $periodAfter,
            'turnover_type' => intval($rule['turnover_type'] ?? 1),
            'tier_calc_mode' => intval($rule['tier_calc_mode'] ?? 1),
        ]);

        foreach ($items as $item) {
            $account = $this->getAccountById(intval($item['ra_id']));
            if (!$account) {
                $this->logRevenueConfig('分账账户查询', [
                    'source' => $isLegacyDeviceRule ? 'device_rule' : 'base_rule',
                    'ra_id' => intval($item['ra_id']),
                    'item_key' => intval($item['item_key']),
                    'found_account' => 0,
                ]);
                continue;
            }
            if (intval($item['calc_type']) === 4 && intval($rule['tier_calc_mode'] ?? 1) === 2) {
                $calc = $this->calculateTierSplitAmount($item, $baseAmount, $periodBefore, $periodAfter);
            } else {
                $calc = $this->calculateRuleItemAmount($item, $baseAmount, $periodAfter);
            }
            if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) {
                continue;
            }
            $baseAllocatedAmount = bcadd($baseAllocatedAmount, $calc['income_amount'], 2);
            if (bccomp($baseAllocatedAmount, $baseAmount, 2) > 0) {
                throw new \Exception("基础分账策略{$rule['rr_id']}分账金额不能超过可分金额");
            }
            $this->records[] = $this->buildRecord([
                'rule_mode' => $ruleMode,
                'rrcfg_id' => $rule['rr_id'],
                'payer_ao_id' => intval($this->order['ao_id'] ?? 0),
                'receiver_ao_id' => intval($item['receiver_ao_id']),
                'calc_type' => intval($item['calc_type']),
                'income_value' => $calc['income_value'],
                'income_amount' => $calc['income_amount'],
                'period_key' => $periodKey,
                'period_amount_before' => $periodBefore,
                'period_amount_after' => $periodAfter,
                'source' => intval($item['calc_type']) === 4 ? 'tier' : ($isLegacyDeviceRule ? 'device_rule' : 'base_rule'),
            ], $account, $rule);
        }

        return true;
    }

    protected function getBaseRuleByMode($mode)
    {
        $rule = $this->getRuleByMode($mode);
        if ($rule) {
            return $rule;
        }
        foreach ($this->details as $detail) {
            $rule = $this->getRuleByMode($mode, $detail);
            if ($rule) {
                return $rule;
            }
        }
        return null;
    }

    protected function calculateRuleItemAmount(array $item, $baseAmount, $periodAfter)
    {
        $calcType = intval($item['calc_type']);
        if ($calcType === 1) {
            $value = $this->money($item['calc_value'] ?? 0, 3);
            return [
                'income_value' => $value,
                'income_amount' => $this->percent($baseAmount, $value),
            ];
        }
        if ($calcType === 2) {
            $amount = $this->money($item['calc_value'] ?? 0);
            return [
                'income_value' => $amount,
                'income_amount' => $amount,
            ];
        }
        if ($calcType === 3) {
            return [
                'income_value' => 100,
                'income_amount' => $baseAmount,
            ];
        }
        if ($calcType === 4) {
            $tier = $this->getMatchedTier($item, $periodAfter);
            if (!$tier) {
                return [
                    'income_value' => '0.000',
                    'income_amount' => '0.00',
                ];
            }
            $value = $this->money($tier['calc_value'] ?? 0, 3);
            return [
                'income_value' => $value,
                'income_amount' => $this->percent($baseAmount, $value),
            ];
        }
        return null;
    }

    protected function calculateProductRuleItemAmount(array $item, $baseAmount, $quantity)
    {
        $calcType = intval($item['calc_type']);
        if ($calcType === 1) {
            return $this->calculateRuleItemAmount($item, $baseAmount, '0');
        }
        if ($calcType === 2) {
            $value = $this->money($item['calc_value'] ?? 0);
            return [
                'income_value' => $value,
                'income_amount' => $this->money(bcmul($value, (string)max(0, intval($quantity)), 2)),
            ];
        }
        return null;
    }

    protected function calculateCouponRuleItemAmount(array $item, $baseAmount)
    {
        $calcType = intval($item['calc_type']);
        if ($calcType === 1) {
            return $this->calculateRuleItemAmount($item, $baseAmount, '0');
        }
        if ($calcType === 2) {
            $value = $this->money($item['calc_value'] ?? 0);
            return [
                'income_value' => $value,
                'income_amount' => $value,
            ];
        }
        return null;
    }

    protected function applyCouponCostAssume($calc, array $coupon)
    {
        if (!$calc) {
            return $calc;
        }
        $costAssume = intval($coupon['cost_assume'] ?? 0);
        if (!in_array($costAssume, [1, 2], true)) {
            return $calc;
        }
        $discountAmount = $this->getRevenueCouponDiscountAmount();
        if (bccomp($discountAmount, '0.01', 2) < 0) {
            return $calc;
        }
        $incomeAmount = bcsub($this->money($calc['income_amount'] ?? 0), $discountAmount, 2);
        $calc['income_amount'] = $this->money($incomeAmount);
        return $calc;
    }

    protected function getRevenueCouponDiscountAmount()
    {
        return $this->money($this->order['revenue_coupon_discount_amount'] ?? 0);
    }

    protected function calculateTierSplitAmount(array $item, $baseAmount, $periodBefore, $periodAfter)
    {
        $tiers = $this->getItemTiers($item);
        if (!$tiers) {
            throw new \Exception("分账接收方{$item['item_key']}未配置阶梯区间");
        }
        $incomeAmount = '0.00';
        foreach ($tiers as $tier) {
            $min = $this->money($tier['threshold_min'] ?? 0);
            $max = $tier['threshold_max'] === null || $tier['threshold_max'] === ''
                ? null
                : $this->money($tier['threshold_max']);
            $overlapStart = bccomp($periodBefore, $min, 2) > 0 ? $periodBefore : $min;
            $overlapEnd = $max === null || bccomp($periodAfter, $max, 2) < 0 ? $periodAfter : $max;
            if (bccomp($overlapEnd, $overlapStart, 2) <= 0) continue;
            $overlapAmount = $this->money(bcsub($overlapEnd, $overlapStart, 2));
            $incomeAmount = bcadd(
                $incomeAmount,
                $this->percent($overlapAmount, $this->money($tier['calc_value'] ?? 0, 3)),
                2
            );
        }
        $effectivePercent = bccomp($baseAmount, '0', 2) > 0
            ? $this->money(bcmul(bcdiv($incomeAmount, $baseAmount, 6), '100', 6), 3)
            : '0.000';
        return [
            'income_value' => $effectivePercent,
            'income_amount' => $this->money($incomeAmount),
        ];
    }

    protected function buildRecord(array $data, array $account, array $rule)
    {
        return array_merge([
            'order_id' => $this->order['order_id'] ?? 0,
            'trade_no' => $this->order['trade_no'] ?? '',
            'sp_id' => $this->order['sp_id'] ?? 0,
            'm_id' => $this->order['m_id'] ?? 0,
            'machine_id' => $this->order['machine_id'] ?? '',
            'machine_name' => $this->order['machine_name'] ?? '',
            'order_amount' => $this->money($this->order['total_price'] ?? 0),
            'ra_id' => $account['ra_id'] ?? 0,
            'manager_id' => $account['manager_id'] ?? 0,
            'manager_name' => $account['manager_name'] ?? '',
            'account_type' => $account['account_type'] ?? '',
            'account' => $account['account'] ?? ($account['bill_account'] ?? ''),
            'settlement_type' => intval($rule['settlement_type'] ?? 1) ?: 1,
            'settlement_days' => max(0, intval($rule['settlement_days'] ?? 0)),
            'status' => 0,
        ], $data);
    }

    protected function saveRecords()
    {
        $locked = RevenueOrderModel::where(['order_id' => $this->order['order_id']])
            ->where('status', '>', 0)
            ->find();
        if ($locked) {
            throw new \Exception("订单已存在非待支付分账记录，不允许重算");
        }
        $this->validateRecordAmounts();
        $this->clearPendingRecords();
        if (!$this->records) {
            return true;
        }
        $this->ensureRevenueOrderSnapshotColumns();
        foreach ($this->records as $record) {
            $record['create_time'] = time();
            $record['update_time'] = time();
            try {
                RevenueOrderModel::create($record);
            } catch (\Exception $e) {
                $message = $this->resolveRevenueOrderMissingFieldMessage($e->getMessage());
                if ($message) {
                    throw new \Exception($message);
                }
                throw $e;
            }
        }
        return true;
    }

    protected function resolveRevenueOrderMissingFieldMessage($message)
    {
        if (!preg_match('/fields not exists:\[([a-zA-Z0-9_]+)\]/', $message, $matches)) {
            return '';
        }
        $field = $matches[1];
        $messages = [
            'g_id' => '设备商品分账缺少 revenue_order.g_id 字段，请先执行 文档说明/设备商品分账数据库变更.sql',
            'mg_id' => '设备商品分账缺少 revenue_order.mg_id 字段，请先执行 文档说明/设备商品分账数据库变更.sql',
        ];
        return $messages[$field] ?? '';
    }

    protected function ensureRevenueOrderSnapshotColumns()
    {
        $required = [];
        foreach ($this->records as $record) {
            if (array_key_exists('g_id', $record) || array_key_exists('mg_id', $record)) {
                $required['g_id'] = '设备商品分账缺少 revenue_order.g_id 字段，请先执行 文档说明/设备商品分账数据库变更.sql';
                $required['mg_id'] = '设备商品分账缺少 revenue_order.mg_id 字段，请先执行 文档说明/设备商品分账数据库变更.sql';
            }
        }
        if (!$required) {
            return true;
        }
        $columns = Db::query("SHOW COLUMNS FROM `revenue_order`");
        $fields = array_column($columns, 'Field');
        foreach ($required as $field => $message) {
            if (!in_array($field, $fields, true)) {
                throw new \Exception($message);
            }
        }
        return true;
    }


    protected function logRevenueConfig($stage, array $data = [])
    {
        try {
            $base = [
                'order_id' => intval($this->order['order_id'] ?? 0),
                'trade_no' => $this->order['trade_no'] ?? '',
                'sp_id' => intval($this->order['sp_id'] ?? 0),
                'm_id' => intval($this->order['m_id'] ?? 0),
                'machine_id' => $this->order['machine_id'] ?? '',
            ];
            actionLog(array_merge($base, $data), '新分账配置-' . $stage, 'RevenueCalculator');
        } catch (\Exception $e) {
            // 日志失败不影响订单分账主流程。
        }
    }

    protected function collectColumnValues(array $rows, $column)
    {
        $values = [];
        foreach ($rows as $row) {
            if (!isset($row[$column]) || $row[$column] === '') {
                continue;
            }
            $value = intval($row[$column]);
            if (!in_array($value, $values, true)) {
                $values[] = $value;
            }
        }
        return $values;
    }

    protected function clearPendingRecords()
    {
        if (empty($this->order['order_id'])) return true;
        RevenueOrderModel::where(['order_id' => $this->order['order_id'], 'status' => 0])->delete();
        return true;
    }

    protected function shouldCalculateRevenue()
    {
        $payType = intval($this->order['pay_type'] ?? 0);
        if ($payType <= 0) {
            $this->logRevenueConfig('支付类型查询', [
                'pay_type' => $payType,
                'found_channel' => 0,
                'skip_reason' => 'empty_pay_type',
            ]);
            return false;
        }

        $channel = RevenuePayChannelModel::where(['pay_type' => $payType, 'status' => 1])
            ->find();
        if ($channel && !is_array($channel)) {
            $channel = $channel->toArray();
        }
        $this->logRevenueConfig('支付类型查询', [
            'pay_type' => $payType,
            'found_channel' => $channel ? 1 : 0,
            'rpc_id' => $channel ? intval($channel['rpc_id'] ?? 0) : 0,
        ]);
        if (!$channel) return false;
        $this->revenuePayChannel = $channel;
        return true;
    }

    protected function getRuleByMode($mode, array $detail = [])
    {
        $rules = $this->getRulesByMode($mode, $detail);
        $rule = $rules ? $rules[0] : null;
        $this->logRevenueConfig('分账规则查询', [
            'rule_mode' => intval($mode),
            'found_rule' => $rule ? 1 : 0,
            'rr_id' => $rule ? intval($rule['rr_id']) : 0,
            'config_name' => $rule ? ($rule['config_name'] ?? '') : '',
        ]);
        return $rule;
    }

    protected function getRulesByMode($mode, array $detail = [])
    {
        $mId = intval($this->order['m_id'] ?? 0);
        $gId = intval($detail['g_id'] ?? 0);
        $mgId = intval($detail['mg_id'] ?? 0);
        $query = RevenueRuleConfigScopeModel::alias('rrcs')
            ->join('revenue_rule_config rrc', 'rrc.rrcfg_id = rrcs.rrcfg_id')
            ->where([
                'rrcs.status' => 1,
                'rrc.status' => 1,
                'rrc.rule_mode' => intval($mode),
            ])
            ->whereIn('rrcs.m_id', [0, $mId]);
        if ($detail) {
            $query->whereIn('rrcs.g_id', [0, $gId]);
            $query->whereIn('rrcs.mg_id', [0, $mgId]);
        } else {
            $query->where(['rrcs.g_id' => 0, 'rrcs.mg_id' => 0]);
        }
        $rules = $query
            ->field('rrc.*')
            ->group('rrc.rrcfg_id')
            ->orderRaw($this->getScopeOrderRaw($mId, $gId, $mgId))
            ->select()
            ->toArray();
        foreach ($rules as &$rule) {
            $rule = $this->normalizeConfigRule($rule);
        }
        unset($rule);
        return $this->filterRulesByPayType($rules);
    }

    protected function filterRulesByPayType(array $rules)
    {
        $result = [];
        foreach ($rules as $rule) {
            if ($this->ruleAllowsPayType($rule)) {
                $result[] = $rule;
            } else {
                $this->logRevenueConfig('分账规则收款方式过滤', [
                    'rule_mode' => intval($rule['rule_mode'] ?? 0),
                    'rr_id' => intval($rule['rr_id'] ?? 0),
                    'pay_type' => intval($this->order['pay_type'] ?? 0),
                    'trigger_pay_types' => $this->getRuleTriggerPayTypes($rule),
                ]);
            }
        }
        return $result;
    }

    protected function ruleAllowsPayType(array $rule)
    {
        $payTypes = $this->getRuleTriggerPayTypes($rule);
        if (!$payTypes) {
            return true;
        }
        return in_array(intval($this->order['pay_type'] ?? 0), $payTypes, true);
    }

    protected function getRuleTriggerPayTypes(array $rule)
    {
        $payTypes = $rule['trigger_pay_types'] ?? [];
        if (is_string($payTypes)) {
            $decoded = json_decode($payTypes, true);
            $payTypes = is_array($decoded) ? $decoded : explode(',', $payTypes);
        }
        if (!is_array($payTypes)) {
            return [];
        }
        $result = [];
        foreach ($payTypes as $payType) {
            if ($payType === '' || $payType === null) continue;
            $payType = intval($payType);
            if (!in_array($payType, $result, true)) $result[] = $payType;
        }
        sort($result);
        return $result;
    }

    protected function getScopeOrderRaw($mId, $gId, $mgId)
    {
        $mId = intval($mId);
        $gId = intval($gId);
        $mgId = intval($mgId);
        return "rrcs.sort asc,"
            . " CASE"
            . " WHEN rrcs.m_id = {$mId} AND {$mgId} > 0 AND rrcs.mg_id = {$mgId} THEN 1"
            . " WHEN rrcs.m_id = {$mId} AND {$gId} > 0 AND rrcs.g_id = {$gId} THEN 2"
            . " WHEN rrcs.m_id = {$mId} AND rrcs.g_id = 0 AND rrcs.mg_id = 0 THEN 3"
            . " WHEN rrcs.m_id = 0 AND {$gId} > 0 AND rrcs.g_id = {$gId} THEN 4"
            . " ELSE 5 END asc,"
            . " rrcs.rrcs_id desc";
    }

    protected function getRentalRuleItem($rrId, $receiverAoId)
    {
        $rule = RevenueRuleConfigModel::where(['rrcfg_id' => intval($rrId), 'status' => 1])->find();
        if (!$rule) {
            return null;
        }
        if (!is_array($rule)) {
            $rule = $rule->toArray();
        }
        foreach ($this->getRuleItems($this->normalizeConfigRule($rule)) as $item) {
            if (intval($item['receiver_ao_id'] ?? 0) === intval($receiverAoId)) {
                return $item;
            }
        }
        return null;
    }

    protected function getRentalRuleItemFromRule(array $rule, $receiverAoId)
    {
        foreach ($this->getRuleItems($rule) as $item) {
            if (intval($item['receiver_ao_id'] ?? 0) === intval($receiverAoId)) {
                return $item;
            }
        }
        return null;
    }

    protected function normalizeConfigRule(array $rule)
    {
        $rule['rr_id'] = intval($rule['rrcfg_id'] ?? ($rule['rr_id'] ?? 0));
        return $rule;
    }

    protected function getRuleItems(array $rule)
    {
        $config = $rule['receiver_config'] ?? [];
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($config)) {
            return [];
        }
        $items = [];
        foreach ($config as $index => $item) {
            if (!is_array($item) || intval($item['status'] ?? 1) !== 1) {
                continue;
            }
            $item['rr_id'] = intval($rule['rr_id'] ?? 0);
            $item['item_key'] = intval($item['item_key'] ?? ($index + 1));
            $item['receiver_ao_id'] = intval($item['receiver_ao_id'] ?? 0);
            $item['ra_id'] = intval($item['ra_id'] ?? 0);
            $item['manager_id'] = intval($item['manager_id'] ?? 0);
            $item['g_id'] = intval($item['g_id'] ?? 0);
            $item['mg_id'] = intval($item['mg_id'] ?? 0);
            $item['calc_type'] = intval($item['calc_type'] ?? 0);
            $item['calc_value'] = $item['calc_value'] ?? 0;
            $item['sort'] = intval($item['sort'] ?? 0);
            $items[] = $item;
        }
        usort($items, function ($left, $right) {
            $sort = intval($left['sort'] ?? 0) - intval($right['sort'] ?? 0);
            return $sort !== 0 ? $sort : intval($left['item_key'] ?? 0) - intval($right['item_key'] ?? 0);
        });
        return $items;
    }

    protected function getItemTiers(array $item)
    {
        $tiers = $item['tiers'] ?? [];
        if (is_string($tiers)) {
            $decoded = json_decode($tiers, true);
            $tiers = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($tiers)) {
            return [];
        }
        $result = [];
        foreach ($tiers as $index => $tier) {
            if (!is_array($tier) || intval($tier['status'] ?? 1) !== 1) {
                continue;
            }
            $tier['tier_key'] = intval($tier['tier_key'] ?? ($index + 1));
            $tier['threshold_min'] = $tier['threshold_min'] ?? 0;
            $tier['threshold_max'] = $tier['threshold_max'] ?? null;
            $tier['calc_value'] = $tier['calc_value'] ?? 0;
            $result[] = $tier;
        }
        usort($result, function ($left, $right) {
            if (floatval($left['threshold_min'] ?? 0) == floatval($right['threshold_min'] ?? 0)) {
                return intval($left['tier_key'] ?? 0) - intval($right['tier_key'] ?? 0);
            }
            return floatval($left['threshold_min'] ?? 0) < floatval($right['threshold_min'] ?? 0) ? -1 : 1;
        });
        return $result;
    }

    protected function validateRecordAmounts()
    {
        $orderTotal = $this->money($this->order['total_price'] ?? 0);
        $total = '0.00';
        $sodTotals = [];
        foreach ($this->records as $record) {
            $amount = $this->money($record['income_amount'] ?? 0);
            if (bccomp($amount, '0', 2) < 0) {
                throw new \Exception("分账金额不能小于0");
            }
            $total = bcadd($total, $amount, 2);
            if (in_array(intval($record['rule_mode'] ?? 0), [2, 4], true) && !empty($record['sod_id'])) {
                $sodId = intval($record['sod_id']);
                if (!isset($sodTotals[$sodId])) {
                    $sodTotals[$sodId] = [
                        'total' => '0.00',
                        'limit' => $this->money($record['sod_total_price'] ?? 0),
                    ];
                }
                $sodTotals[$sodId]['total'] = bcadd($sodTotals[$sodId]['total'], $amount, 2);
            }
        }
        if (bccomp($total, $orderTotal, 2) > 0) {
            throw new \Exception("分账总额不能超过订单金额");
        }
        foreach ($sodTotals as $sodId => $item) {
            if (bccomp($item['total'], $item['limit'], 2) > 0) {
                    throw new \Exception("子订单{$sodId}分账金额不能超过子订单金额");
            }
        }
        return true;
    }

    protected function getDeviceRuleBaseAmount(array $rule)
    {
        $orderAmount = $this->money($this->order['total_price'] ?? 0);
        if (intval($rule['base_type'] ?? 1) === 2) {
            return $this->money(bcsub($orderAmount, $this->rentalAmount, 2));
        }
        return $orderAmount;
    }

    protected function getBaseRuleAmount(array $rule, array $items, $allocatedAmount)
    {
        if (intval($rule['rule_mode'] ?? 1) === 3) {
            return $this->getDeviceRuleBaseAmount($rule);
        }
        $orderAmount = $this->money($this->order['total_price'] ?? 0);
        if ($this->hasTierRuleItem($items)) {
            if (intval($rule['base_type'] ?? 2) === 1) {
                return $orderAmount;
            }
            return $this->money(bcsub($orderAmount, $this->rentalAmount, 2));
        }
        return $this->money(bcsub($orderAmount, $allocatedAmount, 2));
    }

    protected function hasTierRuleItem(array $items)
    {
        foreach ($items as $item) {
            if (intval($item['calc_type'] ?? 0) === 4) {
                return true;
            }
        }
        return false;
    }

    protected function getAccountById($raId)
    {
        $account = RevenueAccountModel::alias('ra')
            ->leftJoin('auth_manager am', 'am.manager_id = ra.manager_id')
            ->where(['ra.ra_id' => $raId, 'ra.status' => 1])
            ->field('ra.*,am.nickname manager_name')
            ->find();
        return $account && !is_array($account) ? $account->toArray() : $account;
    }

    protected function getMatchedTier(array $item, $amount)
    {
        $tiers = $this->getItemTiers($item);
        foreach ($tiers as $tier) {
            $min = $this->money($tier['threshold_min'] ?? 0);
            $max = $tier['threshold_max'] === null || $tier['threshold_max'] === '' ? null : $this->money($tier['threshold_max']);
            $gteMin = bccomp($amount, $min, 2) >= 0;
            $ltMax = $max === null || bccomp($amount, $max, 2) < 0;
            if ($gteMin && $ltMax) {
                return $tier;
            }
        }
        return null;
    }

    protected function getMachineMonthlyTurnover($mId, $periodKey, $turnoverType = 1)
    {
        if ($mId <= 0) return '0.00';
        $start = strtotime($periodKey . '-01 00:00:00');
        $end = strtotime(date('Y-m-t 23:59:59', $start));
        $query = SaleOrdersModel::where('m_id', $mId)
            ->where('pay_status', 3)
            ->whereBetween('pay_time', [$start, $end]);
        $total = $this->money($query->sum('total_price'));
        if (intval($turnoverType) === 1) {
            $refund = $this->money(SaleOrdersModel::where('m_id', $mId)
                ->where('pay_status', 3)
                ->whereBetween('pay_time', [$start, $end])
                ->sum('refund_amount'));
            $total = $this->money(bcsub($total, $refund, 2));
        }
        return bccomp($total, '0', 2) < 0 ? '0.00' : $total;
    }

    protected function percent($amount, $percent)
    {
        return $this->money(bcmul($amount, bcdiv($percent, '100', 6), 6));
    }

    protected function money($value, $scale = 2)
    {
        if ($value === null || $value === '') {
            $value = 0;
        }
        return bcadd((string)$value, '0', $scale);
    }
}
