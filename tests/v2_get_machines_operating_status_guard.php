<?php

$client = file_get_contents(dirname(__DIR__) . '/app/AppFactory/Api/V2/V2Client.php');
$machineController = file_get_contents(dirname(__DIR__) . '/app/management/controller/machine/Machine.php');
$legacyDocument = file_get_contents(dirname(__DIR__) . '/文档说明/嘉潮汇对外API接口文档.doc');
$openApiPath = dirname(__DIR__) . '/文档说明/V2获取机器信息在营状态.apifox.openapi.json';
$openApi = json_decode(file_get_contents($openApiPath), true);

$machineSchema = $openApi['components']['schemas']['MachineInfo']['properties'] ?? [];
$checks = [
    'get_machines 查询 machine.is_operating' => strpos($client, 'inventory_location,is_operating"') !== false,
    'get_machines 不覆盖原始在营状态' => strpos($client, "\$machine['is_operating'] =") === false,
    '在营状态描述使用单一映射' => strpos($client, '$isOperatingDescMap = [') !== false
        && substr_count($client, "['is_operating_desc'] =") === 1,
    '未知在营状态返回空描述' => strpos($client, '$isOperatingDescMap[$isOperating] ?? ""') !== false,
    '后台导出状态 3 统一为外售' => strpos($machineController, "when 3 then '外售' END) is_operating") !== false,
    'OpenAPI 包含 get_machines 接口' => isset($openApi['paths']['/api/v2']['post']),
    'OpenAPI 在营状态枚举正确' => ($machineSchema['is_operating']['enum'] ?? []) === [1, 2, 3],
    'OpenAPI 在营状态描述枚举正确' => ($machineSchema['is_operating_desc']['enum'] ?? []) === ['在营', '在库', '外售', ''],
    '旧版接口文档包含在营状态字段' => strpos($legacyDocument, '&quot;is_operating&quot;: 1') !== false
        && strpos($legacyDocument, '&quot;is_operating_desc&quot;: &quot;在营&quot;') !== false
        && strpos($legacyDocument, '设备在营状态，1：在营，2：在库，3：外售') !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed) {
    fwrite(STDERR, "FAILED:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "OK: V2 get_machines operating status guard passed\n";
