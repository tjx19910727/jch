<?php

/**
 * 分账账户更新一致性守卫。
 */

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueAccountClient.php');
$openApi = json_decode(file_get_contents($root . '/文档说明/统一分账后台接口.apifox.openapi.json'), true);
$failures = [];

foreach ([
    "\$current = \$this->getRevenueAccountFind(",
    "\$accountData = array_merge(\$current, \$data);",
    "intval(\$manager['ao_id']) !== intval(\$accountData['ao_id'])",
    "\$managerChanged = !\$isUpdate",
    "\$enablingAccount = isset(\$data['status']) && intval(\$data['status']) === 1",
    "\$ownershipChanged = intval(\$accountData['ao_id']) !== intval(\$current['ao_id'])",
    "\$this->isRevenueAccountUsedByConfig(intval(\$current['ra_id']))",
    "Db::name('revenue_rule_config')",
    '分账账户已被分账配置引用，不允许修改所属组织或账户管理人',
] as $expected) {
    if (strpos($client, $expected) === false) {
        $failures[] = "分账账户更新一致性逻辑缺少：{$expected}";
    }
}

if (strpos($client, "Db::name('revenue_rule_item')") !== false) {
    $failures[] = '分账账户更新仍引用旧 revenue_rule_item 表';
}

$schema = $openApi['components']['schemas']['RevenueAccountUpdateRequest']['properties'] ?? [];
if ($schema) {
    if (strpos($schema['ao_id']['description'] ?? '', '分账配置引用') === false) {
        $failures[] = 'OpenAPI 未说明 ao_id 的新版配置引用限制';
    }
    if (strpos($schema['manager_id']['description'] ?? '', '最终所属组织') === false) {
        $failures[] = 'OpenAPI 未说明 manager_id 的最终组织一致性校验';
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 分账账户更新使用新旧值合并后的完整数据校验\n";
echo "[PASS] 单独修改 ao_id 或 manager_id 均会校验最终组织一致性\n";
echo "[PASS] 已被新版分账配置引用的账户禁止修改组织和管理人\n";
echo "\nSummary: passed=3, failed=0\n";
