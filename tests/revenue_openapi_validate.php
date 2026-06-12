<?php

$file = dirname(__DIR__) . '/文档说明/新分账后台接口.apifox.openapi.json';
$json = json_decode(file_get_contents($file), true);
if (!is_array($json)) {
    fail('OpenAPI JSON 无效：' . json_last_error_msg());
}

$failures = [];
$paths = $json['paths'] ?? [];
$schemas = $json['components']['schemas'] ?? [];
$requestBodies = $json['components']['requestBodies'] ?? [];
$security = $json['components']['securitySchemes']['TokenAuth'] ?? [];
$tokenHeader = $json['components']['parameters']['TokenHeader'] ?? [];
$scenarioGuides = $json['x-scenario-guides'] ?? [];
$organizationFields = $json['x-response-organization-fields']['fields'] ?? [];
$payTypeFields = $json['x-response-pay-type-fields']['fields'] ?? [];
$tagNames = array_column($json['tags'] ?? [], 'name');
$actualPaths = [
    '/management/revenue.revenue_account/getList',
    '/management/revenue.revenue_account/getFind',
    '/management/revenue.revenue_account/add',
    '/management/revenue.revenue_account/update',
    '/management/revenue.revenue_account/del',
    '/management/revenue.revenue_pay_channel/getList',
    '/management/revenue.revenue_pay_channel/getFind',
    '/management/revenue.revenue_pay_channel/add',
    '/management/revenue.revenue_pay_channel/update',
    '/management/revenue.revenue_pay_channel/del',
    '/management/revenue.revenue_rule/getList',
    '/management/revenue.revenue_rule/getFind',
    '/management/revenue.revenue_rule/add',
    '/management/revenue.revenue_rule/update',
    '/management/revenue.revenue_rule/addItem',
    '/management/revenue.revenue_rule/updateItem',
    '/management/revenue.revenue_rule/addProductItem',
    '/management/revenue.revenue_rule/getItemList',
    '/management/revenue.revenue_rule/delItem',
    '/management/revenue.revenue_rule/addTier',
    '/management/revenue.revenue_rule/updateTier',
    '/management/revenue.revenue_rule/getTierList',
    '/management/revenue.revenue_rule/delTier',
    '/management/revenue.revenue_rule/bindMachine',
    '/management/revenue.revenue_rule/getMachineList',
    '/management/revenue.revenue_rule/getBoundMachineList',
    '/management/revenue.revenue_rule/unbindMachine',
    '/management/revenue.revenue_order/getList',
    '/management/revenue.revenue_order/getFind',
    '/management/revenue.revenue_order/getDetail',
    '/management/revenue.revenue_order/export',
    '/management/revenue.revenue_order/mockPaySuccess',
];

check(($json['openapi'] ?? '') === '3.0.3', 'OpenAPI 版本必须为 3.0.3', $failures);
check(($security['type'] ?? '') === 'apiKey', 'TokenAuth 必须为 apiKey', $failures);
check(($security['in'] ?? '') === 'header', 'TokenAuth 必须位于 Header', $failures);
check(($security['name'] ?? '') === 'token', 'TokenAuth Header 名必须为 token', $failures);
check(strpos($security['x-apifox-example'] ?? '', '{{token}}') !== false, 'TokenAuth 示例必须为 {{token}}', $failures);
check(isset($json['security'][0]['TokenAuth']), '必须全局启用 TokenAuth', $failures);
check(($tokenHeader['name'] ?? '') === 'token', 'TokenHeader 参数名必须为 token', $failures);
check(($tokenHeader['in'] ?? '') === 'header', 'TokenHeader 必须位于 Header', $failures);
check(($tokenHeader['required'] ?? false) === true, 'TokenHeader 必须为必传参数', $failures);
check(($tokenHeader['example'] ?? '') === '{{token}}', 'TokenHeader 参数值必须为 {{token}}', $failures);
check(count($paths) === 32, '新分账接口数量应为 32', $failures);
check(($json['info']['version'] ?? '') === '2.1.1', '重新生成的 OpenAPI 版本必须为 2.1.1', $failures);
check(($json['x-generated-at'] ?? '') === '2026-06-12', 'OpenAPI 必须记录本次重新生成日期', $failures);
check(count($tagNames) === 4, '新分账接口应只保留4个实际模块标签', $failures);
check(!in_array('新分账-收款策略配置', $tagNames, true), '不得保留已删除的收款策略配置标签', $failures);
check(array_keys($paths) === $actualPaths, 'OpenAPI 路径必须与当前 Revenue 控制器完全一致', $failures);
check(count($scenarioGuides) >= 5, '必须提供普通、出租、固定比例、阶梯、T+N等场景索引', $failures);
check(($organizationFields['ao_id'] ?? '') === 'organization_name', 'ao_id 返回字段必须补充 organization_name', $failures);
check(($organizationFields['payer_ao_id'] ?? '') === 'payer_organization_name', 'payer_ao_id 返回字段必须补充 payer_organization_name', $failures);
check(($organizationFields['receiver_ao_id'] ?? '') === 'receiver_organization_name', 'receiver_ao_id 返回字段必须补充 receiver_organization_name', $failures);
check(($payTypeFields['pay_type'] ?? '') === 'pay_type_desc', 'pay_type 返回字段必须补充 pay_type_desc', $failures);
foreach (['RevenueRuleListRequest', 'RevenueRuleAddRequest', 'RevenueRuleUpdateRequest'] as $name) {
    check(strpos(json_encode($schemas[$name] ?? []), 'payer_ao_id') === false, "{$name} 不得继续暴露 payer_ao_id", $failures);
}
check(strpos(json_encode($schemas['RevenueOrderListRequest'] ?? []), 'payer_ao_id') !== false, 'RevenueOrderListRequest 必须保留订单收款组织筛选', $failures);

foreach ($paths as $path => $pathItem) {
    check(strpos($path, '/management/revenue.') === 0, "接口不属于独立新分账模块：{$path}", $failures);
    check(isset($pathItem['post']), "接口必须使用 POST：{$path}", $failures);
    $operation = $pathItem['post'] ?? [];
    check(!empty($operation['summary']), "接口缺少 summary：{$path}", $failures);
    check(!empty($operation['description']), "接口缺少 description：{$path}", $failures);
    $parameterRefs = array_column($operation['parameters'] ?? [], '$ref');
    check(
        in_array('#/components/parameters/TokenHeader', $parameterRefs, true),
        "接口未显式携带必传 token Header：{$path}",
        $failures
    );
    if (!empty($operation['requestBody']['$ref'])) {
        $name = refName($operation['requestBody']['$ref']);
        check(isset($requestBodies[$name]), "请求体引用不存在：{$path} -> {$name}", $failures);
        $examples = $requestBodies[$name]['content']['application/json']['examples'] ?? [];
        check(!empty($examples), "接口缺少命名场景请求示例：{$path} -> {$name}", $failures);
    }
}

foreach ([
    'RevenueAccountAddRequest',
    'RevenuePayChannelAddRequest',
    'RevenueRuleAddRequest',
    'RevenueRuleItemAddRequest',
    'RevenueRuleTierAddRequest',
    'RevenueRuleBindMachineRequest',
    'RevenueOrderListRequest',
    'RevenueOrderMockPaySuccessRequest',
] as $name) {
    $examples = $requestBodies[$name]['content']['application/json']['examples'] ?? [];
    check(count($examples) >= 3, "核心接口必须提供至少3个场景示例：{$name}", $failures);
}

foreach ($schemas as $name => $schema) {
    validateSchema($name, $schema, $schemas, $failures);
}

if ($failures) {
    foreach ($failures as $failure) {
        echo "[FAIL] {$failure}\n";
    }
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] OpenAPI JSON 有效\n";
echo "[PASS] 32 个接口均属于独立新分账模块并使用 POST\n";
echo "[PASS] 所有接口均显式携带必传 Header token: {{token}}\n";
echo "[PASS] 全部接口均提供命名场景请求示例，核心接口具备多场景参数\n";
echo "[PASS] 请求体引用、必填字段和字段说明校验通过\n";
echo "[PASS] 组织ID与组织名称返回字段约定完整\n";
echo "[PASS] 支付类型与支付类型说明返回字段约定完整\n";
echo "[PASS] 文档已按当前 Revenue 控制器重新生成，无失效模块或接口\n";
echo "\nSummary: passed=8, failed=0\n";

function validateSchema(string $name, array $schema, array $schemas, array &$failures): void
{
    foreach ($schema['required'] ?? [] as $required) {
        check(isset($schema['properties'][$required]), "Schema {$name} 的必填字段 {$required} 未定义", $failures);
    }
    foreach ($schema['properties'] ?? [] as $property => $definition) {
        check(!empty($definition['description']), "Schema {$name}.{$property} 缺少字段说明", $failures);
    }
    foreach ($schema['allOf'] ?? [] as $part) {
        if (!empty($part['$ref'])) {
            $ref = refName($part['$ref']);
            check(isset($schemas[$ref]), "Schema {$name} 引用不存在：{$ref}", $failures);
        }
        if (is_array($part)) {
            validateSchema($name . '.allOf', $part, $schemas, $failures);
        }
    }
}

function refName(string $ref): string
{
    $parts = explode('/', $ref);
    return end($parts);
}

function check(bool $condition, string $message, array &$failures): void
{
    if (!$condition) {
        $failures[] = $message;
    }
}

function fail(string $message): void
{
    echo "[FAIL] {$message}\n";
    exit(1);
}
