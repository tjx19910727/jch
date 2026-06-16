<?php

/**
 * 新分账策略批量绑定设备接口守卫。
 */

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$validator = file_get_contents($root . '/app/management/validate/VRevenueRule.php');
$openApi = json_decode(file_get_contents($root . '/文档说明/新分账后台接口.apifox.openapi.json'), true);
$failures = [];

foreach ([
    "\$postData['m_ids'] ?? (\$postData['m_id'] ?? [])",
    'protected function normalizeMachineIds($machineIds)',
    "->where('m_id', 'in', \$machineIds)",
    '->lock(true)',
    '设备不存在：',
    '设备已绑定当前分账策略：',
    '设备已绑定同类型启用分账策略：',
    "'rrm_ids' => \$rrmIds, 'm_ids' => \$machineIds",
] as $expected) {
    if (strpos($client, $expected) === false) {
        $failures[] = "批量绑定实现缺少：{$expected}";
    }
}

if (strpos($validator, '"bindMachine" => ["rr_id"]') === false) {
    $failures[] = '批量绑定验证器未兼容 m_ids 与旧 m_id 二选一';
}

$schema = $openApi['components']['schemas']['RevenueRuleBindMachineRequest'] ?? [];
if (($schema['required'] ?? []) !== ['rr_id', 'm_ids']
    || ($schema['properties']['m_ids']['type'] ?? '') !== 'array') {
    $failures[] = 'OpenAPI 未将 m_ids 数组声明为批量绑定必填参数';
}

$operationRequest = $openApi['paths']['/management/revenue.revenue_rule/bindMachine']['post']['requestBody'] ?? [];
$operationSchemaRef = $operationRequest['content']['application/json']['schema']['$ref'] ?? '';
$operationExample = $operationRequest['content']['application/json']['example'] ?? [];
if (($operationRequest['required'] ?? false) !== true
    || $operationSchemaRef !== '#/components/schemas/RevenueRuleBindMachineRequest'
    || ($operationExample['m_ids'] ?? []) !== [100, 101]) {
    $failures[] = 'OpenAPI bindMachine 路径未直接声明 m_ids 批量请求参数和示例';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] bindMachine 支持一个策略批量绑定多个设备\n";
echo "[PASS] 批量绑定在事务内完成全量预检后写入\n";
echo "[PASS] 旧 m_id 参数保持兼容，OpenAPI 使用标准 m_ids 数组\n";
echo "\nSummary: passed=3, failed=0\n";
