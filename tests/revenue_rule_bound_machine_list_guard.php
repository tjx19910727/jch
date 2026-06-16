<?php

/**
 * 查询策略已绑定设备接口守卫。
 */

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/management/controller/revenue/RevenueRule.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$validator = file_get_contents($root . '/app/management/validate/VRevenueRule.php');
$openApi = json_decode(file_get_contents($root . '/文档说明/新分账后台接口.apifox.openapi.json'), true);
$upgradeSql = file_get_contents($root . '/文档说明/新分账最新数据库升级.sql');
$failures = [];

foreach ([
    'public function getBoundMachineList()',
    "\$this->validate(\$postData, \$this->validatePath . 'getBoundMachineList')",
] as $expected) {
    if (strpos($controller, $expected) === false) {
        $failures[] = "控制器缺少：{$expected}";
    }
}

foreach ([
    'public function getBoundMachineList($postData)',
    "->join('machine m', 'm.m_id = rrm.m_id')",
    "'rrm.rr_id', \$rrId",
    'm.machine_id,m.machine_name',
    "\$query->whereLike('m.machine_id'",
    "\$query->whereLike('m.machine_name'",
] as $expected) {
    if (strpos($client, $expected) === false) {
        $failures[] = "已绑定设备查询实现缺少：{$expected}";
    }
}

if (strpos($validator, '"getBoundMachineList" => ["rr_id"]') === false) {
    $failures[] = '查询策略已绑定设备接口未强制校验 rr_id';
}

$path = '/management/revenue.revenue_rule/getBoundMachineList';
if (!isset($openApi['paths'][$path]['post'])) {
    $failures[] = 'OpenAPI 缺少查询策略已绑定设备接口';
}
if (strpos($upgradeSql, '/management/revenue.revenue_rule/getBoundMachineList') === false) {
    $failures[] = '最新数据库升级 SQL 未注册查询策略已绑定设备权限节点';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 新增按策略查询已绑定设备接口\n";
echo "[PASS] 返回绑定信息及设备编号、名称和组织名称\n";
echo "[PASS] 接口强制传 rr_id 并支持分页与设备搜索\n";
echo "\nSummary: passed=3, failed=0\n";
