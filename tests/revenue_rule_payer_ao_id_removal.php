<?php

/**
 * revenue_rule.payer_ao_id 删除边界守卫。
 * revenue_order.payer_ao_id 是订单收款组织快照，必须继续保留。
 */

$root = dirname(__DIR__);
$failures = [];
$ruleClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$orderModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenueOrderModel.php');
$databaseChange = file_get_contents($root . '/文档说明/统一分账配置数据库变更.sql');
$migration = file_get_contents($root . '/文档说明/清理旧分账表和字段.sql');
$openApi = json_decode(file_get_contents($root . '/文档说明/统一分账后台接口.apifox.openapi.json'), true);

if (is_file($root . '/app/AppFactory/Kernel/Model/Revenue/RevenueRuleModel.php')) {
    $failures[] = 'RevenueRuleModel 旧模型文件仍存在';
}
if (substr_count($ruleClient, "unset(\$postData['payer_ao_id']);") < 2
    || strpos($ruleClient, "'payer_ao_id',") !== false) {
    $failures[] = '规则新增或更新接口未忽略旧 payer_ao_id 参数，或更新白名单仍保留该字段';
}
if (preg_match('/CREATE TABLE IF NOT EXISTS `revenue_rule_config` \((.*?)\) ENGINE=/s', $databaseChange, $matches)
    && strpos($matches[1], 'payer_ao_id') !== false) {
    $failures[] = '初始化 SQL 的 revenue_rule_config 仍包含 payer_ao_id';
}
if (strpos($migration, 'DROP TABLE IF EXISTS `revenue_rule`') === false) {
    $failures[] = '缺少旧 revenue_rule 表清理语句';
}
foreach ([
    'SaveConfigRequest',
    'GetListRequest',
    'GetFindRequest',
] as $schemaName) {
    if (strpos(json_encode($openApi['components']['schemas'][$schemaName] ?? []), 'payer_ao_id') !== false) {
        $failures[] = "OpenAPI {$schemaName} 仍暴露 payer_ao_id";
    }
}
if (strpos($orderModel, '"payer_ao_id"') === false) {
    $failures[] = '订单收款组织快照被误删';
}
foreach ([
    '统一分账配置数据库变更.sql',
] as $file) {
    $sql = file_get_contents($root . '/文档说明/' . $file);
    if (preg_match('/INSERT\s+INTO\s+(?:kiosk\.)?revenue_rule_config\s*\([^)]*payer_ao_id/is', $sql)) {
        $failures[] = "{$file} 的 revenue_rule_config INSERT 仍包含 payer_ao_id";
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 旧 revenue_rule 模型已删除，统一配置未暴露 payer_ao_id\n";
echo "[PASS] 已部署数据库具备旧分账表清理脚本\n";
echo "[PASS] revenue_order.payer_ao_id 订单快照和查询筛选保持不变\n";
echo "\nSummary: passed=3, failed=0\n";
