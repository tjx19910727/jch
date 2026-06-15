<?php

/**
 * 收款策略新分账配置移除检查。
 */

$root = dirname(__DIR__);
$calculator = file_get_contents($root . '/app/AppFactory/Kernel/Service/Revenue/RevenueCalculator.php');
$provider = file_get_contents($root . '/app/AppFactory/Kernel/Providers/Management/RevenueProvider.php');
$databaseChange = file_get_contents($root . '/文档说明/新分账数据库初始化.sql');
$failures = [];

foreach ([$calculator, $provider, $databaseChange] as $content) {
    if (stripos($content, 'revenue_payee_config') !== false
        || stripos($content, 'RevenuePayeeConfig') !== false
        || stripos($content, 'revenuePayeeConfig') !== false) {
        $failures[] = '运行代码或初始化 SQL 仍依赖 revenue_payee_config';
        break;
    }
}

if (strpos($calculator, "Db::name('revenue_pay_channel')") === false) {
    $failures[] = '分账入口没有读取渠道开关';
}
if (strpos($calculator, "where(['payee_type'") !== false
    || strpos($calculator, "field('payee_type')") !== false) {
    $failures[] = '分账入口仍依赖 payee_type 回退匹配';
}
if (strpos($calculator, 'getRuleByMode(1)') === false
    || strpos($calculator, '设备未配置普通分账策略') === false) {
    $failures[] = '普通分账没有完整迁移到 rule_mode=1';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 运行代码和初始化 SQL 已移除 revenue_payee_config\n";
echo "[PASS] 支付渠道开关继续控制是否进入分账\n";
echo "[PASS] 普通分账由 rule_mode=1 规则负责\n";
echo "\nSummary: passed=3, failed=0\n";
