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
checkOpenApi(count($paths) === 15, '角色权限模板及账号模板配置接口数量应为 15', $failures);
checkOpenApi(!isset($paths['/management/auth.auth_role_template/apply']), '不应继续暴露旧的模板应用到角色接口', $failures);
checkOpenApi(isset($paths['/management/auth.auth_role_template/getManagers']), '缺少查询模板直接绑定账号接口', $failures);
checkOpenApi(isset($paths['/management/auth.auth_role_template/applyManagers']), '缺少模板批量设置账号接口', $failures);
checkOpenApi(isset($paths['/management/auth.auth_manager/getList']), '缺少账号列表模板绑定查询接口', $failures);
checkOpenApi(isset($paths['/management/auth.auth_manager/getFind']), '缺少账号详情模板绑定查询接口', $failures);
checkOpenApi(isset($paths['/management/auth.auth_manager/add']), '缺少账号新增模板开关配置接口', $failures);
checkOpenApi(isset($paths['/management/auth.auth_manager/update']), '缺少账号修改模板开关配置接口', $failures);

foreach ($paths as $path => $item) {
    $validPrefix = strpos($path, '/management/auth.auth_role_template/') === 0
        || strpos($path, '/management/auth.auth_manager/') === 0;
    checkOpenApi($validPrefix, "接口路径不属于角色权限模板或账号配置相关接口：{$path}", $failures);
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

$encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
checkOpenApi(strpos($encoded, 'authManagerGetListWithRoleTemplate') !== false, 'OpenAPI 缺少账号列表模板绑定说明', $failures);
checkOpenApi(strpos($encoded, 'authManagerUpdateWithRoleTemplateSwitch') !== false, 'OpenAPI 缺少账号模板开关修改说明', $failures);
checkOpenApi(strpos($encoded, 'role_template_name') !== false, 'OpenAPI 缺少账号绑定模板名称字段', $failures);
checkOpenApi(strpos($encoded, '具体绑定哪个模板建议调用 /management/auth.auth_role_template/applyManagers') !== false, 'OpenAPI 应说明模板绑定入口是 applyManagers', $failures);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] OpenAPI JSON 可解析\n";
echo "[PASS] 15 个角色权限模板及账号配置接口均使用 POST\n";
echo "[PASS] 所有接口均携带 token Header，值为 {{token}}\n";
echo "\nSummary: passed=3, failed=0\n";
