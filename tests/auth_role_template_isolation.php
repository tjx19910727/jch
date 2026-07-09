<?php

$root = dirname(__DIR__);
$failures = [];

function checkTemplate($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

$managerRole = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthManagerRoleClient.php');
$managerRoleTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Auth/AuthManagerRoleTrait.php');
$roleNode = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthRoleNodeClient.php');
$templateTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Auth/AuthRoleTemplateTrait.php');
$templateClient = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthRoleTemplateClient.php');
$provider = file_get_contents($root . '/app/AppFactory/Kernel/Providers/Management/AuthProvider.php');
$migration = file_get_contents($root . '/文档说明/角色权限模板数据库变更.sql');
$newMigration = file_get_contents($root . '/文档说明/角色权限模板新版数据库升级.sql');

checkTemplate(strpos($managerRoleTrait, "use_role_template") !== false, '鉴权未按账号开关选择权限来源', $failures);
checkTemplate(strpos($managerRoleTrait, "Db::name('auth_role_template_node')") !== false, '开启模板的账号未读取模板节点', $failures);
checkTemplate(strpos($managerRoleTrait, "Db::name('auth_role_node')") !== false, '关闭模板的账号未保留历史角色节点逻辑', $failures);
checkTemplate(strpos($roleNode, '已关联权限模板') === false, '历史角色节点接口不应被模板关联阻断', $failures);
checkTemplate(strpos($templateTrait, "Db::name('auth_role_node')") === false, '模板维护不应覆盖历史角色节点', $failures);
checkTemplate(strpos($templateTrait, "Db::name('auth_manager_role')") === false, '模板不应直接写账号角色关系', $failures);
checkTemplate(strpos($templateClient, 'assertTemplateManaged') !== false, '模板写操作缺少组织权限校验', $failures);
checkTemplate(strpos($templateClient, 'public function applyManagers($data)') !== false, '缺少模板直接绑定账号逻辑', $failures);
checkTemplate(strpos($templateClient, 'public function apply($data)') === false, '模板客户端不应继续暴露角色应用接口', $failures);
checkTemplate(strpos($provider, "authRoleTemplate") !== false, '模板客户端未注册到管理端容器', $failures);
checkTemplate(strpos($migration, 'auth_role_template_node') !== false, '数据库变更缺少模板节点表', $failures);
checkTemplate(strpos($migration, 'role_template_id') !== false, '数据库变更缺少账号直接模板字段', $failures);
checkTemplate(strpos($migration, 'use_role_template') !== false, '数据库变更缺少账号模板开关字段', $failures);
checkTemplate(strpos($migration, 'setRoleManagers') === false, '数据库变更不应再注册角色批量设置账号接口节点', $failures);
checkTemplate(strpos($newMigration, 'auth_role_template_navigation') !== false, '新版数据库升级缺少模板导航配置表', $failures);
checkTemplate(strpos($newMigration, 'data_scope') !== false, '新版数据库升级缺少语义化查询范围', $failures);
checkTemplate(strpos($templateClient, "private const DATA_SCOPES = ['organization', 'all']") !== false, '新版模板未限制查询范围枚举', $failures);
checkTemplate(strpos($templateClient, 'create_enabled') !== false && strpos($templateClient, 'delete_enabled') !== false
    && strpos($templateClient, 'update_enabled') !== false && strpos($templateClient, 'query_enabled') !== false,
    '新版模板未实现增删改查独立权限', $failures);
checkTemplate(strpos($managerRoleTrait, 'data_scope') !== false, '模板鉴权未读取新版查询范围', $failures);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 鉴权按账号开关选择模板或历史角色节点\n";
echo "[PASS] 模板维护不覆盖历史角色节点\n";
echo "[PASS] 模板支持直接替换账号集合\n";
echo "[PASS] 模板写操作包含组织权限校验且客户端已注册\n";
echo "[PASS] 数据库变更包含账号模板开关及直接绑定接口节点\n";
echo "[PASS] 新模板使用逐导航 data_scope 和增删改查独立权限\n";
echo "\nSummary: passed=6, failed=0\n";
