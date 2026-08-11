<?php

/**
 * 分账支付类型未配置时必须记录明确原因，且不能阻断正常支付。
 */

$root = dirname(__DIR__);
$calculator = file_get_contents($root . '/app/AppFactory/Kernel/Service/Revenue/RevenueCalculator.php');
$failures = [];

foreach ([
    "'pay_method' => intval(\$this->order['pay_method'] ?? 0)",
    "'skip_reason' => \$channel ? '' : 'no_enabled_pay_type'",
    'if (!$channel) return false;',
] as $expected) {
    if (strpos($calculator, $expected) === false) {
        $failures[] = "分账渠道未命中日志或非阻断行为缺失：{$expected}";
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] missing revenue pay type is observable and does not block payment\n";
echo "\nSummary: passed=3, failed=0\n";
