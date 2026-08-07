<?php

/**
 * 新分账离线自动化测试。
 *
 * 不连接数据库，不调用真实支付。用内存数据验证核心业务规则：
 * 普通分账、设备出租、设备固定比例分账、阶梯分账、支付成功/取消状态流转。
 */

class OfflineRevenueCalculator
{
    private array $accounts;
    private array $rules;
    private array $machineRules;
    private array $enabledPayTypes;
    private array $monthlyTurnover;
    private float $rentalAmount = 0.0;
    private float $productAmount = 0.0;
    private array $rentalAmountsBySod = [];

    public function __construct(array $fixture)
    {
        $this->accounts = $fixture['accounts'];
        $this->rules = $fixture['rules'];
        $this->machineRules = $fixture['machine_rules'];
        $this->enabledPayTypes = $fixture['enabled_pay_types'];
        $this->monthlyTurnover = $fixture['monthly_turnover'];
    }

    public function calculate(array $order): array
    {
        $this->rentalAmount = 0.0;
        $this->productAmount = 0.0;
        $this->rentalAmountsBySod = [];
        $records = [];
        if (!in_array((int)$order['pay_type'], $this->enabledPayTypes, true)) {
            return [];
        }

        $records = array_merge($records, $this->calculateRental($order));
        $productRecords = $this->calculateProductRule($order);
        $deviceRecords = $productRecords ? [] : $this->calculateDeviceRule($order);
        if ($productRecords) {
            $records = array_merge($records, $productRecords);
            $records = array_merge($records, $this->normalRecords($order));
        } elseif ($deviceRecords) {
            $records = array_merge($records, $deviceRecords);
        } else {
            $records = array_merge($records, $this->normalRecords($order));
        }

        $total = $this->sum($records, 'income_amount');
        $this->assert($total <= $order['total_price'] + 0.0001, '分账总额不能超过订单金额');
        return $records;
    }

    public function settle(array $records, int $payTime = 1780000000): array
    {
        foreach ($records as &$record) {
            if (in_array((int)$record['status'], [0, 2], true)) {
                if ((int)($record['settlement_type'] ?? 1) === 2) {
                    $record['status'] = 2;
                    $record['planned_revenue_time'] = strtotime('+' . max(1, (int)($record['settlement_days'] ?? 0)) . ' day', strtotime(date('Y-m-d 00:00:00', $payTime)));
                    continue;
                }
                $record['status'] = 1;
                $record['revenue_time'] = 1780000000;
            }
        }
        return $records;
    }

    public function settleDue(array $records, int $now): array
    {
        foreach ($records as &$record) {
            if ((int)$record['status'] === 2 && (int)($record['planned_revenue_time'] ?? 0) <= $now) {
                $record['status'] = 1;
                $record['revenue_time'] = $now;
            }
        }
        return $records;
    }

    public function cancel(array $records): array
    {
        foreach ($records as &$record) {
            if ((int)$record['status'] === 0) {
                $record['status'] = 4;
            }
        }
        return $records;
    }

    private function calculateRental(array $order): array
    {
        $rule = $this->getMachineRule($order['m_id'], 2);
        if ($rule === null) return [];
        $records = [];
        foreach ($order['details'] as $detail) {
            if ((int)$detail['sod_ao_id'] === (int)$order['ao_id']) {
                continue;
            }
            $item = $this->findRuleItem($rule, (int)$detail['sod_ao_id']);
            if ($item === null || !isset($this->accounts[$item['ra_id']])) {
                continue;
            }

            $amount = $this->calcAmount($detail['total_sod_price'], $item);
            $this->assert($amount <= $detail['total_sod_price'] + 0.0001, '设备出租分账金额不能超过商品金额');
            $this->rentalAmount += $amount;
            $this->rentalAmountsBySod[$detail['sod_id']] = ($this->rentalAmountsBySod[$detail['sod_id']] ?? 0.0) + $amount;
            $records[] = $this->record($order, $item, [
                'sod_id' => $detail['sod_id'],
                'sod_total_price' => $detail['total_sod_price'],
                'rule_mode' => 2,
                'source' => 'rental',
                'income_amount' => $amount,
            ], $rule);
        }
        return $records;
    }

    private function calculateProductRule(array $order): array
    {
        $rule = $this->getMachineRule($order['m_id'], 4);
        if (!$rule) return [];
        if (empty($rule['items'])) return [];
        $records = [];
        foreach ($order['details'] as $detail) {
            $baseAmount = $detail['total_sod_price'] - ($this->rentalAmountsBySod[$detail['sod_id']] ?? 0.0);
            $detailAllocatedAmount = 0.0;
            foreach ($rule['items'] as $item) {
                if ((int)($item['g_id'] ?? 0) !== (int)($detail['g_id'] ?? 0)) continue;
                if (!isset($this->accounts[$item['ra_id']])) continue;
                $amount = (int)$item['calc_type'] === 2
                    ? round($item['calc_value'] * $detail['quantity'], 2)
                    : $this->calcAmount($baseAmount, $item);
                $detailAllocatedAmount += $amount;
                $this->assert($detailAllocatedAmount <= $baseAmount + 0.0001, '设备商品分账金额不能超过商品可分金额');
                $this->productAmount += $amount;
                $records[] = $this->record($order, $item, [
                    'sod_id' => $detail['sod_id'],
                    'g_id' => $detail['g_id'],
                    'mg_id' => $detail['mg_id'] ?? 0,
                    'sod_total_price' => $detail['total_sod_price'],
                    'rule_mode' => 4,
                    'source' => 'product_rule',
                    'income_amount' => $amount,
                ], $rule);
            }
        }
        return $records;
    }

    private function calculateDeviceRule(array $order): array
    {
        $rule = $this->getMachineRule($order['m_id'], 3);
        if (!$rule) {
            return [];
        }
        if (empty($rule['items'])) {
            return [];
        }

        $baseAmount = $order['total_price'];
        if ((int)$rule['base_type'] === 2) {
            $baseAmount -= $this->rentalAmount;
        }
        $periodKey = '2026-06';
        $periodBefore = $this->monthlyTurnover[$order['m_id']][$periodKey] ?? 0.0;
        $periodAfter = $periodBefore + $baseAmount;

        $records = [];
        $deviceAllocatedAmount = 0.0;
        foreach ($rule['items'] as $item) {
            if (!isset($this->accounts[$item['ra_id']])) continue;
            $amount = (int)$item['calc_type'] === 4 && (int)($rule['tier_calc_mode'] ?? 1) === 2
                ? $this->calcTierSplitAmount($baseAmount, $periodBefore, $periodAfter, $item)
                : $this->calcAmount($baseAmount, $item, $periodAfter);
            $deviceAllocatedAmount += $amount;
            $this->assert($deviceAllocatedAmount <= $baseAmount + 0.0001, '设备分账金额不能超过设备规则可分金额');
            $records[] = $this->record($order, $item, [
                'rule_mode' => 3,
                'source' => (int)$item['calc_type'] === 4 ? 'tier' : 'device_rule',
                'income_amount' => $amount,
                'period_key' => $periodKey,
                'period_amount_before' => $periodBefore,
                'period_amount_after' => $periodAfter,
                'income_value' => $item['_matched_value'] ?? $item['calc_value'],
            ], $rule);
        }
        return $records;
    }

    private function calcTierSplitAmount(float $baseAmount, float $periodBefore, float $periodAfter, array &$item): float
    {
        $income = 0.0;
        foreach ($item['tiers'] as $tier) {
            $start = max($periodBefore, (float)$tier['threshold_min']);
            $end = $tier['threshold_max'] === null
                ? $periodAfter
                : min($periodAfter, (float)$tier['threshold_max']);
            if ($end <= $start) continue;
            $part = $end - $start;
            $income += $part * (float)$tier['calc_value'] / 100;
        }
        $item['_matched_value'] = $baseAmount > 0 ? round($income / $baseAmount * 100, 3) : 0;
        return round($income, 2);
    }

    private function normalRecords(array $order): array
    {
        $normalAmount = round($order['total_price'] - $this->rentalAmount - $this->productAmount, 2);
        if ($normalAmount < 0.01) return [];
        $rule = $this->getMachineRule($order['m_id'], 1);
        if ($rule === null) return [];
        if (empty($rule['items'])) return [];
        $records = [];
        $normalAllocatedAmount = 0.0;
        foreach ($rule['items'] as $item) {
            if (!isset($this->accounts[$item['ra_id']])) continue;
            $amount = $this->calcAmount($normalAmount, $item);
            if ($amount < 0.01) continue;
            $normalAllocatedAmount += $amount;
            $this->assert($normalAllocatedAmount <= $normalAmount + 0.0001, '普通分账金额不能超过订单剩余金额');
            $records[] = $this->record($order, $item, [
                'rule_mode' => 1,
                'source' => 'normal',
                'income_amount' => $amount,
            ], $rule);
        }
        return $records;
    }

    private function calcAmount(float $baseAmount, array &$item, float $periodAfter = 0.0): float
    {
        if ((int)$item['calc_type'] === 1) {
            return round($baseAmount * $item['calc_value'] / 100, 2);
        }
        if ((int)$item['calc_type'] === 2) {
            return round($item['calc_value'], 2);
        }
        if ((int)$item['calc_type'] === 3) {
            return round($baseAmount, 2);
        }
        if ((int)$item['calc_type'] === 4) {
            foreach ($item['tiers'] as $tier) {
                $max = $tier['threshold_max'];
                if ($periodAfter >= $tier['threshold_min'] && ($max === null || $periodAfter < $max)) {
                    $item['_matched_value'] = $tier['calc_value'];
                    return round($baseAmount * $tier['calc_value'] / 100, 2);
                }
            }
            $item['_matched_value'] = 0.0;
            return 0.0;
        }
        throw new RuntimeException('未知计算方式');
    }

    private function record(array $order, array $item, array $extra, array $rule): array
    {
        $account = $this->accounts[$item['ra_id']];
        return $this->baseRecord($order, $account, array_merge([
            'receiver_ao_id' => $item['receiver_ao_id'],
            'ra_id' => $item['ra_id'],
            'manager_id' => $item['manager_id'],
            'calc_type' => $item['calc_type'],
            'income_value' => $item['calc_value'],
            'settlement_type' => $rule['settlement_type'] ?? 1,
            'settlement_days' => $rule['settlement_days'] ?? 0,
        ], $extra));
    }

    private function baseRecord(array $order, array $account, array $extra): array
    {
        return array_merge([
            'order_id' => $order['order_id'],
            'trade_no' => $order['trade_no'],
            'sp_id' => $order['sp_id'],
            'm_id' => $order['m_id'],
            'payer_ao_id' => $order['ao_id'],
            'order_amount' => $order['total_price'],
            'account_type' => $account['account_type'],
            'account' => $account['account'],
            'status' => 0,
        ], $extra);
    }

    private function getMachineRule(int $mId, int $mode): ?array
    {
        foreach ($this->machineRules[$mId] ?? [] as $ruleId) {
            $rule = $this->rules[$ruleId];
            if ((int)$rule['rule_mode'] === $mode) {
                return $rule;
            }
        }
        return null;
    }

    private function findRuleItem(array $rule, int $receiverAoId): ?array
    {
        foreach ($rule['items'] as $item) {
            if ((int)$item['receiver_ao_id'] === $receiverAoId) {
                return $item;
            }
        }
        return null;
    }

    private function sum(array $records, string $field): float
    {
        return array_reduce($records, fn($carry, $item) => $carry + (float)$item[$field], 0.0);
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}

function fixture(): array
{
    return [
        'accounts' => [
            101 => ['ra_id' => 101, 'ao_id' => 1, 'manager_id' => 1001, 'account_type' => 'balance', 'account' => 'A_BALANCE'],
            102 => ['ra_id' => 102, 'ao_id' => 2, 'manager_id' => 1002, 'account_type' => 'balance', 'account' => 'B_BALANCE'],
            103 => ['ra_id' => 103, 'ao_id' => 3, 'manager_id' => 1003, 'account_type' => 'balance', 'account' => 'C_BALANCE'],
        ],
        'enabled_pay_types' => [1],
        'rules' => [
            200 => [
                'rr_id' => 200,
                'rule_mode' => 1,
                'settlement_type' => 1,
                'settlement_days' => 0,
                'items' => [
                    ['rri_id' => 300, 'receiver_ao_id' => 1, 'ra_id' => 101, 'manager_id' => 1001, 'calc_type' => 3, 'calc_value' => 100.0],
                ],
            ],
            201 => [
                'rr_id' => 201,
                'rule_mode' => 2,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 301, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 1, 'calc_value' => 100.0],
                ],
            ],
            202 => [
                'rr_id' => 202,
                'rule_mode' => 3,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 302, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 1, 'calc_value' => 20.0],
                    ['rri_id' => 303, 'receiver_ao_id' => 3, 'ra_id' => 103, 'manager_id' => 1003, 'calc_type' => 1, 'calc_value' => 30.0],
                ],
            ],
            203 => [
                'rr_id' => 203,
                'rule_mode' => 3,
                'base_type' => 1,
                'items' => [
                    [
                        'rri_id' => 304,
                        'receiver_ao_id' => 1,
                        'ra_id' => 101,
                        'manager_id' => 1001,
                        'calc_type' => 4,
                        'calc_value' => 0.0,
                        'tiers' => [
                            ['threshold_min' => 0.0, 'threshold_max' => 5000.0, 'calc_value' => 20.0],
                            ['threshold_min' => 5000.0, 'threshold_max' => null, 'calc_value' => 25.0],
                        ],
                    ],
                    [
                        'rri_id' => 305,
                        'receiver_ao_id' => 2,
                        'ra_id' => 102,
                        'manager_id' => 1002,
                        'calc_type' => 4,
                        'calc_value' => 0.0,
                        'tiers' => [
                            ['threshold_min' => 0.0, 'threshold_max' => 8000.0, 'calc_value' => 25.0],
                            ['threshold_min' => 8000.0, 'threshold_max' => null, 'calc_value' => 30.0],
                        ],
                    ],
                ],
            ],
            204 => [
                'rr_id' => 204,
                'rule_mode' => 4,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 306, 'g_id' => 700, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 1, 'calc_value' => 10.0],
                ],
            ],
            205 => [
                'rr_id' => 205,
                'rule_mode' => 4,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 307, 'g_id' => 700, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 2, 'calc_value' => 3.0],
                ],
            ],
            206 => [
                'rr_id' => 206,
                'rule_mode' => 1,
                'settlement_type' => 2,
                'settlement_days' => 1,
                'items' => [
                    ['rri_id' => 308, 'receiver_ao_id' => 1, 'ra_id' => 101, 'manager_id' => 1001, 'calc_type' => 3, 'calc_value' => 100.0],
                ],
            ],
            207 => [
                'rr_id' => 207,
                'rule_mode' => 2,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 309, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 2, 'calc_value' => 5.0],
                ],
            ],
            208 => [
                'rr_id' => 208,
                'rule_mode' => 2,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 310, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 3, 'calc_value' => 100.0],
                ],
            ],
            209 => [
                'rr_id' => 209,
                'rule_mode' => 3,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 311, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 2, 'calc_value' => 5.0],
                ],
            ],
            210 => [
                'rr_id' => 210,
                'rule_mode' => 3,
                'base_type' => 1,
                'items' => [
                    ['rri_id' => 312, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 3, 'calc_value' => 100.0],
                ],
            ],
            211 => [
                'rr_id' => 211,
                'rule_mode' => 3,
                'base_type' => 2,
                'items' => [
                    ['rri_id' => 313, 'receiver_ao_id' => 1, 'ra_id' => 101, 'manager_id' => 1001, 'calc_type' => 3, 'calc_value' => 100.0],
                ],
            ],
            212 => [
                'rr_id' => 212,
                'rule_mode' => 3,
                'base_type' => 1,
                'tier_calc_mode' => 2,
                'items' => [
                    [
                        'rri_id' => 314,
                        'receiver_ao_id' => 2,
                        'ra_id' => 102,
                        'manager_id' => 1002,
                        'calc_type' => 4,
                        'calc_value' => 0.0,
                        'tiers' => [
                            ['threshold_min' => 0.0, 'threshold_max' => 5000.0, 'calc_value' => 10.0],
                            ['threshold_min' => 5000.0, 'threshold_max' => 8000.0, 'calc_value' => 20.0],
                            ['threshold_min' => 8000.0, 'threshold_max' => null, 'calc_value' => 30.0],
                        ],
                    ],
                ],
            ],
            213 => [
                'rr_id' => 213,
                'rule_mode' => 3,
                'base_type' => 1,
                'turnover_type' => 2,
                'tier_calc_mode' => 1,
                'items' => [
                    [
                        'rri_id' => 315,
                        'receiver_ao_id' => 2,
                        'ra_id' => 102,
                        'manager_id' => 1002,
                        'calc_type' => 4,
                        'calc_value' => 0.0,
                        'tiers' => [
                            ['threshold_min' => 0.0, 'threshold_max' => 8000.0, 'calc_value' => 20.0],
                            ['threshold_min' => 8000.0, 'threshold_max' => null, 'calc_value' => 30.0],
                        ],
                    ],
                ],
            ],
        ],
        'machine_rules' => [
            501 => [200],
            502 => [200, 201],
            503 => [202],
            504 => [203],
            505 => [200, 204],
            506 => [200, 205],
            507 => [206],
            508 => [200, 202, 204],
            509 => [200, 207],
            510 => [200, 208],
            511 => [209],
            512 => [210],
            513 => [201, 211],
            514 => [212],
            515 => [213],
        ],
        'monthly_turnover' => [
            504 => ['2026-06' => 4900.0],
            514 => ['2026-06' => 4900.0],
            515 => ['2026-06' => 7900.0],
        ],
    ];
}

function orderFixture(int $orderId, int $mId, float $total, array $details): array
{
    return [
        'order_id' => $orderId,
        'trade_no' => 'CODEX_REV_TEST_' . $orderId,
        'sp_id' => 9001,
        'pay_type' => 1,
        'pay_method' => 1,
        'm_id' => $mId,
        'machine_id' => 'CODEX-M-' . $mId,
        'machine_name' => 'CODEX测试设备' . $mId,
        'ao_id' => 1,
        'total_price' => $total,
        'details' => $details,
    ];
}

function assertEquals($expected, $actual, string $message): void
{
    if ($expected != $actual) {
        throw new RuntimeException($message . '，期望=' . json_encode($expected) . '，实际=' . json_encode($actual));
    }
}

function assertMoney(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.001) {
        throw new RuntimeException($message . "，期望={$expected}，实际={$actual}");
    }
}

$tests = [];

$tests['普通分账：A设备销售A商品，A获得订单全额'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10001, 501, 100.0, [
        ['sod_id' => 1, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(1, count($records), '应生成1条分账单');
    assertEquals(1, $records[0]['rule_mode'], '应为普通分账');
    assertMoney(100.0, $records[0]['income_amount'], '普通分账金额应为订单全额');
};

$tests['支付类型关闭：不生成分账单'] = function () {
    $fixture = fixture();
    $fixture['enabled_pay_types'] = [];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10011, 501, 100.0, [
        ['sod_id' => 11, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(0, count($records), '支付类型关闭时不应生成分账单');
};

$tests['支付类型判断：读取订单pay_type'] = function () {
    $fixture = fixture();
    $fixture['enabled_pay_types'] = [12];
    $calc = new OfflineRevenueCalculator($fixture);
    $order = orderFixture(10028, 501, 100.0, [
        ['sod_id' => 28, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]);
    $order['pay_type'] = 1;
    $records = $calc->calculate($order);
    assertEquals(0, count($records), 'pay_type未命中时不应触发分账');
};

$tests['缺少普通规则：存在剩余金额时不生成普通分账'] = function () {
    $fixture = fixture();
    $fixture['machine_rules'][501] = [];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10012, 501, 100.0, [
        ['sod_id' => 12, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(0, count($records), '缺少普通规则时应允许未分配金额留在收款账户');
};

$tests['仅配置支付类型：未配置任何分账策略不应中断'] = function () {
    $fixture = fixture();
    $fixture['machine_rules'][599] = [];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10029, 599, 80.0, [
        ['sod_id' => 29, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(0, count($records), '只开启支付渠道但没有分账策略时应跳过分账');
};

$tests['缺少出租规则：出租商品不应中断支付流程'] = function () {
    $fixture = fixture();
    $fixture['machine_rules'][501] = [200];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10030, 501, 80.0, [
        ['sod_id' => 30, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(1, count($records), '缺少出租规则时应跳过出租分账并继续普通分账');
    assertEquals('normal', $records[0]['source'], '剩余金额应继续按普通规则处理');
};

$tests['分账策略配置不完整：缺少明细或账户时不应中断'] = function () {
    $fixture = fixture();
    $fixture['rules'][220] = ['rr_id' => 220, 'rule_mode' => 3, 'base_type' => 1, 'items' => []];
    $fixture['rules'][221] = [
        'rr_id' => 221,
        'rule_mode' => 1,
        'items' => [
            ['rri_id' => 320, 'receiver_ao_id' => 1, 'ra_id' => 999, 'manager_id' => 1001, 'calc_type' => 1, 'calc_value' => 50.0],
        ],
    ];
    $fixture['machine_rules'][599] = [220, 221];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10031, 599, 80.0, [
        ['sod_id' => 31, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(0, count($records), '策略缺少明细或有效账户时应跳过分账');
};

$tests['设备出租：A设备销售B商品，B按100%获得商品金额'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10002, 502, 80.0, [
        ['sod_id' => 2, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(1, count($records), '应生成1条出租分账单');
    assertEquals('rental', $records[0]['source'], '应为出租分账');
    assertEquals(2, $records[0]['receiver_ao_id'], '接收组织应为B');
    assertMoney(80.0, $records[0]['income_amount'], 'B应获得商品金额100%');
};

$tests['设备固定比例分账：B 20%，C 30%'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10003, 503, 100.0, [
        ['sod_id' => 3, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(2, count($records), '应生成2条设备分账单');
    assertMoney(20.0, $records[0]['income_amount'], 'B应获得20%');
    assertMoney(30.0, $records[1]['income_amount'], 'C应获得30%');
    assertEquals('device_rule', $records[0]['source'], '应为设备策略分账');
};

$tests['普通分账少分或全分：只按配置生成应分账金额'] = function () {
    $fixture = fixture();
    $fixture['rules'][200]['items'] = [
        ['rri_id' => 300, 'receiver_ao_id' => 1, 'ra_id' => 101, 'manager_id' => 1001, 'calc_type' => 1, 'calc_value' => 40.0],
    ];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10022, 501, 100.0, [
        ['sod_id' => 22, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(1, count($records), '普通分账少分或全分时都应生成配置金额');
    assertMoney(40.0, $records[0]['income_amount'], '普通分账应只生成40元');
};

$tests['普通分账超分：应拦截'] = function () {
    $fixture = fixture();
    $fixture['rules'][200]['items'] = [
        ['rri_id' => 300, 'receiver_ao_id' => 1, 'ra_id' => 101, 'manager_id' => 1001, 'calc_type' => 1, 'calc_value' => 60.0],
        ['rri_id' => 301, 'receiver_ao_id' => 2, 'ra_id' => 102, 'manager_id' => 1002, 'calc_type' => 1, 'calc_value' => 60.0],
    ];
    $calc = new OfflineRevenueCalculator($fixture);
    try {
        $calc->calculate(orderFixture(10023, 501, 100.0, [
            ['sod_id' => 23, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
        ]));
        throw new RuntimeException('普通分账超分时没有报错');
    } catch (RuntimeException $e) {
        assertEquals('普通分账金额不能超过订单剩余金额', $e->getMessage(), '普通分账超分应返回明确错误');
    }
};

$tests['同一商品不同设备：设备505按商品金额10%分账'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10009, 505, 100.0, [
        ['sod_id' => 9, 'mg_id' => 9001, 'g_id' => 700, 'sod_ao_id' => 1, 'quantity' => 2, 'retail_price' => 50.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(2, count($records), '应生成商品分账和普通剩余分账');
    assertEquals('product_rule', $records[0]['source'], '应为设备商品分账');
    assertMoney(10.0, $records[0]['income_amount'], '设备505商品应按10%分账');
    assertMoney(90.0, $records[1]['income_amount'], '剩余金额应走普通分账');
};

$tests['同一订单不同规则：分别写入各自结算周期'] = function () {
    $fixture = fixture();
    $fixture['rules'][204]['settlement_type'] = 2;
    $fixture['rules'][204]['settlement_days'] = 2;
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10013, 505, 100.0, [
        ['sod_id' => 13, 'mg_id' => 9003, 'g_id' => 700, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(2, $records[0]['settlement_type'], '商品规则应写入T+N结算类型');
    assertEquals(2, $records[0]['settlement_days'], '商品规则应写入T+2');
    assertEquals(1, $records[1]['settlement_type'], '普通规则应保持即时结算');
    assertEquals(0, $records[1]['settlement_days'], '普通规则即时结算天数应为0');
};

$tests['同一商品不同设备：设备506按每件3元分账'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10010, 506, 100.0, [
        ['sod_id' => 10, 'mg_id' => 9002, 'g_id' => 700, 'sod_ao_id' => 1, 'quantity' => 2, 'retail_price' => 50.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(2, count($records), '应生成商品分账和普通剩余分账');
    assertEquals(2, $records[0]['calc_type'], '应为固定金额分账');
    assertMoney(6.0, $records[0]['income_amount'], '设备506商品应按每件3元、两件共6元分账');
    assertMoney(94.0, $records[1]['income_amount'], '剩余金额应走普通分账');
};

$tests['商品规则未命中：回退设备固定比例分账'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10014, 508, 100.0, [
        ['sod_id' => 14, 'mg_id' => 9004, 'g_id' => 701, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(2, count($records), '商品规则未命中时应生成设备固定比例分账');
    assertEquals('device_rule', $records[0]['source'], '商品规则未命中时应回退设备规则');
    assertMoney(20.0, $records[0]['income_amount'], '回退设备规则后B应获得20%');
    assertMoney(30.0, $records[1]['income_amount'], '回退设备规则后C应获得30%');
};

$tests['设备出租固定金额：每条出租商品分账5元'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10015, 509, 80.0, [
        ['sod_id' => 15, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(2, count($records), '出租固定金额后剩余金额应走普通分账');
    assertMoney(5.0, $records[0]['income_amount'], '出租固定金额应为5元');
    assertMoney(75.0, $records[1]['income_amount'], '剩余75元应走普通分账');
};

$tests['设备出租固定金额超分：应拦截'] = function () {
    $fixture = fixture();
    $fixture['rules'][207]['items'][0]['calc_value'] = 120.0;
    $calc = new OfflineRevenueCalculator($fixture);
    try {
        $calc->calculate(orderFixture(10027, 509, 80.0, [
            ['sod_id' => 27, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
        ]));
        throw new RuntimeException('设备出租分账超分时没有报错');
    } catch (RuntimeException $e) {
        assertEquals('设备出租分账金额不能超过商品金额', $e->getMessage(), '设备出租分账超分应返回明确错误');
    }
};

$tests['设备出租全额：出租组织获得商品全额'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10016, 510, 80.0, [
        ['sod_id' => 16, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(1, count($records), '出租全额后不应再生成普通分账');
    assertMoney(80.0, $records[0]['income_amount'], '出租组织应获得商品全额');
};

$tests['设备固定金额：设备策略分账5元'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10017, 511, 100.0, [
        ['sod_id' => 17, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(1, count($records), '设备固定金额应生成1条分账');
    assertMoney(5.0, $records[0]['income_amount'], '设备固定金额应为5元');
};

$tests['设备固定金额超分：应拦截'] = function () {
    $fixture = fixture();
    $fixture['rules'][209]['items'][0]['calc_value'] = 120.0;
    $calc = new OfflineRevenueCalculator($fixture);
    try {
        $calc->calculate(orderFixture(10024, 511, 100.0, [
            ['sod_id' => 24, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
        ]));
        throw new RuntimeException('设备分账超分时没有报错');
    } catch (RuntimeException $e) {
        assertEquals('设备分账金额不能超过设备规则可分金额', $e->getMessage(), '设备分账超分应返回明确错误');
    }
};

$tests['设备全额：设备策略分账订单全额'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10018, 512, 100.0, [
        ['sod_id' => 18, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 100.0, 'total_sod_price' => 100.0],
    ]));
    assertEquals(1, count($records), '设备全额应生成1条分账');
    assertMoney(100.0, $records[0]['income_amount'], '设备策略应获得订单全额');
};

$tests['设备分账扣除出租基数：出租50%后设备分剩余全额'] = function () {
    $fixture = fixture();
    $fixture['rules'][201]['items'][0]['calc_value'] = 50.0;
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10019, 513, 80.0, [
        ['sod_id' => 19, 'sod_ao_id' => 2, 'quantity' => 1, 'retail_price' => 80.0, 'total_sod_price' => 80.0],
    ]));
    assertEquals(2, count($records), '应生成出租分账和设备剩余基数分账');
    assertMoney(40.0, $records[0]['income_amount'], '出租组织应获得50%');
    assertMoney(40.0, $records[1]['income_amount'], '设备规则应按扣除出租后的40元计算');
};

$tests['设备跨阶梯拆分：本单跨越5000按两档分别计算'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10020, 514, 200.0, [
        ['sod_id' => 20, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 200.0, 'total_sod_price' => 200.0],
    ]));
    assertEquals(1, count($records), '跨阶梯拆分应生成1条聚合分账记录');
    assertMoney(15.0, $records[0]['income_value'], '前100元10%、后100元20%，有效比例应为15%');
    assertMoney(30.0, $records[0]['income_amount'], '跨阶梯拆分金额应为30元');
};

$tests['设备阶梯支付成功金额口径：累计达到8000命中30%'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10021, 515, 200.0, [
        ['sod_id' => 21, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 200.0, 'total_sod_price' => 200.0],
    ]));
    assertEquals(1, count($records), '支付成功金额口径阶梯应生成1条分账');
    assertMoney(30.0, $records[0]['income_value'], '累计达到8000应命中30%');
    assertMoney(60.0, $records[0]['income_amount'], '支付成功金额口径分账应为60元');
};

$tests['设备阶梯区间未覆盖：未命中部分不分'] = function () {
    $fixture = fixture();
    $fixture['rules'][212]['items'][0]['tiers'] = [
        ['threshold_min' => 5000.0, 'threshold_max' => 5050.0, 'calc_value' => 20.0],
    ];
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10025, 514, 200.0, [
        ['sod_id' => 25, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 200.0, 'total_sod_price' => 200.0],
    ]));
    assertEquals(1, count($records), '阶梯只覆盖部分金额时仍应生成命中部分');
    assertMoney(10.0, $records[0]['income_amount'], '只有5000到5050的50元按20%分账');
};

$tests['设备商品按件固定金额超分：应拦截'] = function () {
    $fixture = fixture();
    $fixture['rules'][205]['items'][0]['calc_value'] = 60.0;
    $calc = new OfflineRevenueCalculator($fixture);
    try {
        $calc->calculate(orderFixture(10026, 506, 100.0, [
            ['sod_id' => 26, 'mg_id' => 9005, 'g_id' => 700, 'sod_ao_id' => 1, 'quantity' => 2, 'retail_price' => 50.0, 'total_sod_price' => 100.0],
        ]));
        throw new RuntimeException('设备商品分账超分时没有报错');
    } catch (RuntimeException $e) {
        assertEquals('设备商品分账金额不能超过商品可分金额', $e->getMessage(), '设备商品分账超分应返回明确错误');
    }
};

$tests['阶梯分账：本单后累计达到5000以上但未到8000'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10004, 504, 200.0, [
        ['sod_id' => 4, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 200.0, 'total_sod_price' => 200.0],
    ]));
    assertEquals(2, count($records), '应生成2条阶梯分账单');
    assertMoney(25.0, $records[0]['income_value'], 'A应命中5000以上25%');
    assertMoney(50.0, $records[0]['income_amount'], 'A分账金额应为50');
    assertMoney(25.0, $records[1]['income_value'], 'B应命中8000以下25%');
    assertMoney(50.0, $records[1]['income_amount'], 'B分账金额应为50');
};

$tests['阶梯分账：本单后累计达到8000以上，B命中30%'] = function () {
    $fixture = fixture();
    $fixture['monthly_turnover'][504]['2026-06'] = 7900.0;
    $calc = new OfflineRevenueCalculator($fixture);
    $records = $calc->calculate(orderFixture(10007, 504, 200.0, [
        ['sod_id' => 7, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 200.0, 'total_sod_price' => 200.0],
    ]));
    assertEquals(2, count($records), '应生成2条阶梯分账单');
    assertMoney(25.0, $records[0]['income_value'], 'A应命中5000以上25%');
    assertMoney(50.0, $records[0]['income_amount'], 'A分账金额应为50');
    assertMoney(30.0, $records[1]['income_value'], 'B应命中8000以上30%');
    assertMoney(60.0, $records[1]['income_amount'], 'B分账金额应为60');
};

$tests['支付成功：待支付分账单变为已结算'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10005, 501, 60.0, [
        ['sod_id' => 5, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 60.0, 'total_sod_price' => 60.0],
    ]));
    $records = $calc->settle($records);
    assertEquals(1, $records[0]['status'], '支付成功后应为已结算');
    assertEquals(1780000000, $records[0]['revenue_time'], '应写入结算时间');
};

$tests['支付取消：待支付分账单变为已取消'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $records = $calc->calculate(orderFixture(10006, 501, 60.0, [
        ['sod_id' => 6, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 60.0, 'total_sod_price' => 60.0],
    ]));
    $records = $calc->cancel($records);
    assertEquals(4, $records[0]['status'], '支付取消后应为已取消');
};

$tests['T+1分账：支付成功后待结算，到期后结算'] = function () {
    $calc = new OfflineRevenueCalculator(fixture());
    $order = orderFixture(10008, 507, 60.0, [
        ['sod_id' => 8, 'sod_ao_id' => 1, 'quantity' => 1, 'retail_price' => 60.0, 'total_sod_price' => 60.0],
    ]);
    $payTime = strtotime('2026-06-06 12:00:00');
    $records = $calc->settle($calc->calculate($order), $payTime);
    assertEquals(2, $records[0]['status'], 'T+1支付成功后应为待结算');
    assertEquals(strtotime('2026-06-07 00:00:00'), $records[0]['planned_revenue_time'], 'T+1计划结算时间应为次日零点');
    $records = $calc->settleDue($records, strtotime('2026-06-07 00:01:00'));
    assertEquals(1, $records[0]['status'], 'T+1到期后应为已结算');
};

$passed = 0;
$failed = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        echo "[PASS] {$name}\n";
    } catch (Throwable $e) {
        $failed++;
        echo "[FAIL] {$name}: {$e->getMessage()}\n";
    }
}

echo "\nSummary: passed={$passed}, failed={$failed}\n";
exit($failed > 0 ? 1 : 0);
