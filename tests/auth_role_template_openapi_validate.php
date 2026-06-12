<?php

$file = dirname(__DIR__) . '/文档说明/角色权限模板.apifox.openapi.json';
$json = file_get_contents($file);
$data = json_decode($json, true);
$failures = [];

function checkOpenApi($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

checkOpenApi(is_array($data), 'OpenAPI JSON 解析失败：' . json_last_error_msg(), $failures);
checkOpenApi(($data['openapi'] ?? '') === '3.0.3', 'OpenAPI 版本应为 3.0.3', $failures);
$paths = $data['paths'] ?? [];
checkOpenApi(count($paths) === 11, '角色权限相关接口数量应为 11', $failures);

foreach ($paths as $path => $item) {
    $validPrefix = strpos($path, '/management/auth.auth_role_template/') === 0
        || $path === '/management/auth.auth_manager_role/setRoleManagers';
    checkOpenApi($validPrefix, "接口路径不属于角色权限相关接口：{$path}", $failures);
    checkOpenApi(isset($item['post']), "接口必须使用 POST：{$path}", $failures);
    $parameters = $item['post']['parameters'] ?? [];
    $hasToken = false;
    foreach ($parameters as $parameter) {
        if (($parameter['$ref'] ?? '') === '#/components/parameters/TokenHeader') {
            $hasToken = true;
        }
    }
    checkOpenApi($hasToken, "接口缺少 token Header：{$path}", $failures);
}

$token = $data['components']['parameters']['TokenHeader'] ?? [];
checkOpenApi(($token['name'] ?? '') === 'token', 'Header 名称应为 token', $failures);
checkOpenApi(($token['in'] ?? '') === 'header', 'token 应位于 Header', $failures);
checkOpenApi(($token['example'] ?? '') === '{{token}}', 'token 示例值应为 {{token}}', $failures);
checkOpenApi(($token['schema']['default'] ?? '') === '{{token}}', 'token 默认值应为 {{token}}', $failures);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] OpenAPI JSON 可解析\n";
echo "[PASS] 11 个角色权限相关接口均使用 POST\n";
echo "[PASS] 所有接口均携带 token Header，值为 {{token}}\n";
echo "\nSummary: passed=3, failed=0\n";
