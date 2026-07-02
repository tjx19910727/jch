<?php

/**
 * 新分账后台接口组织名称返回守卫。
 */

$root = dirname(__DIR__);
$trait = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueOrganizationNameTrait.php');
$accountClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueAccountClient.php');
$ruleClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$orderClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueOrderClient.php');
$failures = [];

foreach ([
    "'ao_id' => 'organization_name'",
    "'payer_ao_id' => 'payer_organization_name'",
    "'receiver_ao_id' => 'receiver_organization_name'",
    "->whereIn('ao_id', array_keys(\$organizationIds))",
    "->column('organization_name', 'ao_id')",
] as $expected) {
    if (strpos($trait, $expected) === false) {
        $failures[] = "组织名称补全逻辑缺少：{$expected}";
    }
}

foreach ([
    '分账账户接口' => $accountClient,
    '分账规则接口' => $ruleClient,
    '分账订单接口' => $orderClient,
] as $name => $client) {
    if (strpos($client, 'use RevenueOrganizationNameTrait;') === false
        || strpos($client, 'appendRevenueOrganizationNames(') === false) {
        $failures[] = "{$name}没有接入组织名称补全逻辑";
    }
}

foreach (['payer_organization_name', 'receiver_organization_name'] as $field) {
    if (strpos($orderClient, "'{$field}'") === false) {
        $failures[] = "分账订单导出缺少组织名称字段：{$field}";
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] revenue 后台查询接口统一批量补全组织名称\n";
echo "[PASS] ao_id、payer_ao_id、receiver_ao_id 均有对应名称字段\n";
echo "[PASS] 分账订单导出包含收款和接收组织名称\n";
echo "\nSummary: passed=3, failed=0\n";
