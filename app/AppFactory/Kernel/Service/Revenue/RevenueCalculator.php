<?php

namespace app\AppFactory\Kernel\Service\Revenue;

use think\facade\Db;

class RevenueCalculator
{
    protected $order = [];
    protected $details = [];
    protected $records = [];
    protected $rentalAmount = '0';
    protected $productAmount = '0';
    protected $rentalAmountsBySod = [];
    protected $payeeRevenueConfig = [];
    protected $revenuePayChannel = [];

    public function calculate(array $order)
    {
        $this->order = $order;
        $this->records = [];
        $this->rentalAmount = '0';
        $this->productAmount = '0';
        $this->rentalAmountsBySod = [];
        $this->payeeRevenueConfig = [];
        $this->revenuePayChannel = [];
        if (!$this->shouldCalculateRevenue()) {
            $this->clearPendingRecords();
            return true;
        }
        $this->details = $order['details'] ?? $order['detail'] ?? [];
        if (!$this->details && !empty($order['order_id'])) {
            $this->details = Db::name('sale_orders_details')->where(['order_id' => $order['order_id']])->select()->toArray();
        }
        if (!$this->details) {
            return true;
        }

        $this->calculateRental();
        $hasProductRule = $this->calculateProductRule();
        $hasDeviceRule = $hasProductRule ? false : $this->calculateDeviceRule();

        if (!$hasProductRule && !$hasDeviceRule) {
            $this->calculateNormal();
        } elseif ($hasProductRule) {
            $this->calculateNormal();
        }

        return $this->saveRecords();
    }

    protected function calculateRental()
    {
        $payerAoId = intval($this->order['ao_id'] ?? 0);
        $rentalRule = $this->getRuleByMode(2);
        foreach ($this->details as $detail) {
            $receiverAoId = intval($detail['sod_ao_id'] ?? 0);
            if ($receiverAoId <= 0 || $receiverAoId === $payerAoId) {
                continue;
            }
            if (!$rentalRule) {
                throw new \Exception("设备出租分账策略未配置");
            }
            $item = $this->getRentalRuleItem(intval($rentalRule['rr_id']), $receiverAoId);
            if (!$item) {
                throw new \Exception("商品组织{$receiverAoId}未配置设备出租分账比例");
            }
            $amount = $this->money($detail['total_sod_price'] ?? 0);
            if (bccomp($amount, '0.01', 2) < 0) {
                continue;
            }
            $account = $this->getAccountById(intval($item['ra_id']));
            if (!$account) {
                throw new \Exception("商品组织{$receiverAoId}未配置有效分账账户");
            }
            $calc = $this->calculateRuleItemAmount($item, $amount, '0');
            if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) {
                continue;
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
                'rr_id' => $rentalRule['rr_id'],
                'rri_id' => $item['rri_id'],
                'payer_ao_id' => $payerAoId,
                'receiver_ao_id' => $receiverAoId,
                'calc_type' => intval($item['calc_type']),
                'income_value' => $calc['income_value'],
                'income_amount' => $calc['income_amount'],
                'source' => 'rental',
            ], $account);
        }
    }

    protected function calculateProductRule()
    {
        $rule = $this->getRuleByMode(4);
        if (!$rule) {
            return false;
        }
        $items = Db::name('revenue_rule_item')
            ->where(['rr_id' => $rule['rr_id'], 'status' => 1])
            ->order('sort asc,rri_id asc')
            ->select()
            ->toArray();
        if (!$items) {
            throw new \Exception("设备商品分账策略{$rule['rr_id']}未配置分账明细");
        }

        foreach ($this->details as $detail) {
            $gId = intval($detail['g_id'] ?? 0);
            $sodId = intval($detail['sod_id'] ?? 0);
            $detailAmount = $this->money($detail['total_sod_price'] ?? 0);
            $baseAmount = $this->money(bcsub($detailAmount, $this->rentalAmountsBySod[$sodId] ?? '0', 2));
            if ($gId <= 0 || bccomp($baseAmount, '0.01', 2) < 0) {
                continue;
            }
            foreach ($items as $item) {
                if (intval($item['g_id'] ?? 0) !== $gId) continue;
                $account = $this->getAccountById(intval($item['ra_id']));
                if (!$account) {
                    throw new \Exception("商品{$gId}分账明细{$item['rri_id']}未配置有效分账账户");
                }
                $calc = $this->calculateProductRuleItemAmount($item, $baseAmount, intval($detail['quantity'] ?? 0));
                if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) continue;
                $this->productAmount = bcadd($this->productAmount, $calc['income_amount'], 2);
                $this->records[] = $this->buildRecord([
                    'sod_id' => $sodId,
                    'g_id' => $gId,
                    'mg_id' => intval($detail['mg_id'] ?? 0),
                    'sod_amount' => $detail['retail_price'] ?? 0,
                    'sod_quantity' => intval($detail['quantity'] ?? 0),
                    'sod_total_price' => $detailAmount,
                    'rule_mode' => 4,
                    'rr_id' => $rule['rr_id'],
                    'rri_id' => $item['rri_id'],
                    'payer_ao_id' => intval($this->order['ao_id'] ?? 0),
                    'receiver_ao_id' => intval($item['receiver_ao_id']),
                    'calc_type' => intval($item['calc_type']),
                    'income_value' => $calc['income_value'],
                    'income_amount' => $calc['income_amount'],
                    'source' => 'product_rule',
                ], $account);
            }
        }
        return true;
    }

    protected function calculateDeviceRule()
    {
        $rule = $this->getRuleByMode(3);
        if (!$rule) {
            return false;
        }
        $items = Db::name('revenue_rule_item')
            ->where(['rr_id' => $rule['rr_id'], 'status' => 1])
            ->order('sort asc,rri_id asc')
            ->select()
            ->toArray();
        if (!$items) {
            throw new \Exception("设备分账策略{$rule['rr_id']}未配置分账明细");
        }

        $baseAmount = $this->getDeviceRuleBaseAmount($rule);
        if (bccomp($baseAmount, '0.01', 2) < 0) {
            return true;
        }

        $periodKey = date('Y-m', intval($this->order['pay_time'] ?? 0) ?: time());
        $periodBefore = $this->getMachineMonthlyTurnover(intval($this->order['m_id'] ?? 0), $periodKey, intval($rule['turnover_type'] ?? 1));
        $periodAfter = bcadd($periodBefore, $baseAmount, 2);

        foreach ($items as $item) {
            $account = $this->getAccountById(intval($item['ra_id']));
            if (!$account) {
                throw new \Exception("分账策略明细{$item['rri_id']}未配置有效分账账户");
            }
            $calc = $this->calculateRuleItemAmount($item, $baseAmount, $periodAfter);
            if (!$calc || bccomp($calc['income_amount'], '0.01', 2) < 0) {
                continue;
            }
            $this->records[] = $this->buildRecord([
                'rule_mode' => 3,
                'rr_id' => $rule['rr_id'],
                'rri_id' => $item['rri_id'],
                'rrit_id' => $calc['rrit_id'] ?? null,
                'payer_ao_id' => intval($this->order['ao_id'] ?? 0),
                'receiver_ao_id' => intval($item['receiver_ao_id']),
                'calc_type' => intval($item['calc_type']),
                'income_value' => $calc['income_value'],
                'income_amount' => $calc['income_amount'],
                'period_key' => $periodKey,
                'period_amount_before' => $periodBefore,
                'period_amount_after' => $periodAfter,
                'source' => intval($item['calc_type']) === 4 ? 'tier' : 'device_rule',
            ], $account);
        }

        return true;
    }

    protected function calculateNormal()
    {
        $payerAoId = intval($this->order['ao_id'] ?? 0);
        $allocatedAmount = bcadd($this->rentalAmount, $this->productAmount, 2);
        $normalAmount = $this->money(bcsub($this->money($this->order['total_price'] ?? 0), $allocatedAmount, 2));
        if (bccomp($normalAmount, '0.01', 2) < 0) {
            return true;
        }
        $account = $this->getPayeeDefaultAccount();
        if (!$account) {
            throw new \Exception("收款策略未配置有效默认分账账户");
        }
        $this->records[] = $this->buildRecord([
            'rule_mode' => 1,
            'payer_ao_id' => $payerAoId,
            'receiver_ao_id' => intval($account['ao_id']),
            'calc_type' => 3,
            'income_value' => 100,
            'income_amount' => $normalAmount,
            'source' => 'normal',
        ], $account);
        return true;
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
            $tier = $this->getMatchedTier(intval($item['rri_id']), $periodAfter);
            if (!$tier) {
                throw new \Exception("分账策略明细{$item['rri_id']}未命中阶梯区间");
            }
            $value = $this->money($tier['calc_value'] ?? 0, 3);
            return [
                'rrit_id' => $tier['rrit_id'],
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

    protected function buildRecord(array $data, array $account)
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
            'settlement_type' => intval($this->payeeRevenueConfig['settlement_type'] ?? 1) ?: 1,
            'settlement_days' => max(0, intval($this->payeeRevenueConfig['settlement_days'] ?? 0)),
            'status' => 0,
        ], $data);
    }

    protected function saveRecords()
    {
        $locked = Db::name('revenue_order')
            ->where(['order_id' => $this->order['order_id']])
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
        foreach ($this->records as $record) {
            $record['create_time'] = time();
            $record['update_time'] = time();
            Db::name('revenue_order')->insert($record);
        }
        return true;
    }

    protected function clearPendingRecords()
    {
        if (empty($this->order['order_id'])) return true;
        Db::name('revenue_order')->where(['order_id' => $this->order['order_id'], 'status' => 0])->delete();
        return true;
    }

    protected function shouldCalculateRevenue()
    {
        $spId = intval($this->order['sp_id'] ?? 0);
        $payType = intval($this->order['pay_type'] ?? 0);
        $payeeType = 0;
        if ($spId > 0) {
            $payee = Db::name('strategy_payee')->where(['sp_id' => $spId])->field('payee_type')->find();
            if (!$payee) return false;
            $payeeType = intval($payee['payee_type'] ?? 0);
        }
        $channel = null;
        if ($payType > 0 && ($channel = Db::name('revenue_pay_channel')->where(['pay_type' => $payType, 'status' => 1])->find())) {
            $this->revenuePayChannel = $channel;
        }
        if (!$channel && $payeeType > 0 && ($channel = Db::name('revenue_pay_channel')->where(['pay_type' => $payeeType, 'status' => 1])->find())) {
            $this->revenuePayChannel = $channel;
        }
        if (!$channel && $payeeType > 0 && ($channel = Db::name('revenue_pay_channel')->where(['payee_type' => $payeeType, 'status' => 1])->find())) {
            $this->revenuePayChannel = $channel;
        }
        if (!$channel) return false;
        $config = Db::name('revenue_payee_config')->where(['sp_id' => $spId, 'status' => 1])->find();
        // 渠道配置只控制是否允许进入新分账；未单独配置的收款策略不参与分账，也不阻断支付。
        if (!$config) return false;
        if (intval($config['enable_revenue'] ?? 1) !== 1) return false;
        $this->payeeRevenueConfig = $config;
        return true;
    }

    protected function getRuleByMode($mode)
    {
        return Db::name('revenue_rule_machine')
            ->alias('rrm')
            ->join('revenue_rule rr', 'rr.rr_id = rrm.rr_id')
            ->where([
                'rrm.m_id' => intval($this->order['m_id'] ?? 0),
                'rrm.status' => 1,
                'rr.status' => 1,
                'rr.rule_mode' => intval($mode),
            ])
            ->field('rr.*')
            ->order('rrm.sort asc,rrm.rrm_id desc')
            ->find();
    }

    protected function getRentalRuleItem($rrId, $receiverAoId)
    {
        return Db::name('revenue_rule_item')
            ->where([
                'rr_id' => $rrId,
                'receiver_ao_id' => $receiverAoId,
                'status' => 1,
            ])
            ->order('sort asc,rri_id asc')
            ->find();
    }

    protected function validateRecordAmounts()
    {
        $orderTotal = $this->money($this->order['total_price'] ?? 0);
        $total = '0.00';
        $sodTotals = [];
        foreach ($this->records as $record) {
            $amount = $this->money($record['income_amount'] ?? 0);
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

    protected function getPayeeDefaultAccount()
    {
        $spId = intval($this->order['sp_id'] ?? 0);
        if ($spId <= 0) {
            return null;
        }
        $config = $this->payeeRevenueConfig ?: Db::name('revenue_payee_config')->where(['sp_id' => $spId, 'status' => 1])->find();
        if (!$config || empty($config['default_ra_id'])) {
            return null;
        }
        return $this->getAccountById(intval($config['default_ra_id']));
    }

    protected function getDefaultAccountByAoId($aoId)
    {
        return Db::name('revenue_account')
            ->alias('ra')
            ->leftJoin('auth_manager am', 'am.manager_id = ra.manager_id')
            ->where(['ra.ao_id' => $aoId, 'ra.status' => 1])
            ->field('ra.*,am.nickname manager_name')
            ->order('ra.ra_id desc')
            ->find();
    }

    protected function getAccountById($raId)
    {
        return Db::name('revenue_account')
            ->alias('ra')
            ->leftJoin('auth_manager am', 'am.manager_id = ra.manager_id')
            ->where(['ra.ra_id' => $raId, 'ra.status' => 1])
            ->field('ra.*,am.nickname manager_name')
            ->find();
    }

    protected function getMatchedTier($rriId, $amount)
    {
        $tiers = Db::name('revenue_rule_item_tier')
            ->where(['rri_id' => $rriId, 'status' => 1])
            ->order('threshold_min asc,rrit_id asc')
            ->select()
            ->toArray();
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
        $query = Db::name('sale_orders')
            ->where('m_id', $mId)
            ->where('pay_status', 3)
            ->whereBetween('pay_time', [$start, $end]);
        $total = $this->money($query->sum('total_price'));
        if (intval($turnoverType) === 1) {
            $refund = $this->money(Db::name('sale_orders')
                ->where('m_id', $mId)
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
