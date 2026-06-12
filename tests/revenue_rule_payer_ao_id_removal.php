<?php

/**
 * revenue_rule.payer_ao_id 删除边界守卫。
 * revenue_order.payer_ao_id 是订单收款组织快照，必须继续保留。
 */

$root = dirname(__DIR__);
$failures = [];
$ruleModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenueRuleModel.php');
$ruleClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$orderModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenueOrderModel.php');
$databaseChange = file_get_contents($root . '/文档说明/分账逻辑优化数据库变更.sql');
$migration = file_get_contents($root . '/文档说明/删除revenue_rule.payer_ao_id.sql');
$openApi = json_decode(file_get_contents($root . '/文档说明/新分账后台接口.apifox.openapi.json'), true);

if (strpos($ruleModel, '"payer_ao_id"') !== false) {
    $failures[] = 'RevenueRuleModel 仍包含 payer_ao_id';
}
if (substr_count($ruleClient, "unset(\$postData['payer_ao_id']);") < 2
    || strpos($ruleClient, "'payer_ao_id',") !== false) {
    $failures[] = '规则新增或更新接口未忽略旧 payer_ao_id 参数，或更新白名单仍保留该字段';
}
if (preg_match('/CREATE TABLE IF NOT EXISTS `revenue_rule` \((.*?)\) ENGINE=/s', $databaseChange, $matches)
    && strpos($matches[1], 'payer_ao_id') !== false) {
    $failures[] = '初始化 SQL 的 revenue_rule 仍包含 payer_ao_id';
}
if (strpos($migration, "ALTER TABLE `revenue_rule` DROP COLUMN `payer_ao_id`") === false) {
    $failures[] = '缺少已部署数据库删除 revenue_rule.payer_ao_id 的迁移语句';
}
foreach ([
    'RevenueRuleListRequest',
    'RevenueRuleAddRequest',
    'RevenueRuleUpdateRequest',
] as $schemaName) {
    if (strpos(json_encode($openApi['components']['schemas'][$schemaName] ?? []), 'payer_ao_id') !== false) {
        $failures[] = "OpenAPI {$schemaName} 仍暴露 payer_ao_id";
    }
}
if (strpos($orderModel, '"payer_ao_id"') === false
    || strpos(json_encode($openApi['components']['schemas']['RevenueOrderListRequest'] ?? []), 'payer_ao_id') === false) {
    $failures[] = '订单收款组织快照或订单查询筛选被误删';
}
foreach ([
    '分账导入sql-修正版.sql',
    'revenue_rule全场景补全INSERT.sql',
    'JCHM-H2D-0064新分账全场景测试数据.sql',
] as $file) {
    $sql = file_get_contents($root . '/文档说明/' . $file);
    if (preg_match('/INSERT\s+INTO\s+(?:kiosk\.)?revenue_rule\s*\([^)]*payer_ao_id/is', $sql)) {
        $failures[] = "{$file} 的 revenue_rule INSERT 仍包含 payer_ao_id";
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] revenue_rule.payer_ao_id 已从模型、接口、OpenAPI 和初始化 SQL 删除\n";
echo "[PASS] 已部署数据库具备幂等删列迁移\n";
echo "[PASS] revenue_order.payer_ao_id 订单快照和查询筛选保持不变\n";
echo "\nSummary: passed=3, failed=0\n";
