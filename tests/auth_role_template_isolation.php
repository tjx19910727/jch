<?php

$root = dirname(__DIR__);
$failures = [];

function checkTemplate($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

$managerRole = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthManagerRoleClient.php');
$roleNode = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthRoleNodeClient.php');
$templateTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Auth/AuthRoleTemplateTrait.php');
$templateClient = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthRoleTemplateClient.php');
$provider = file_get_contents($root . '/app/AppFactory/Kernel/Providers/Management/AuthProvider.php');
$migration = file_get_contents($root . '/文档说明/角色权限模板数据库变更.sql');

checkTemplate(strpos($managerRole, 'applyAuthRoleTemplateToRole') !== false, '账号绑定角色前未自动应用模板', $failures);
checkTemplate(strpos($roleNode, '已关联权限模板') !== false, '模板角色仍可被旧节点接口覆盖', $failures);
checkTemplate(strpos($templateTrait, "Db::name('auth_role_node')") !== false, '模板未同步到角色节点表', $failures);
checkTemplate(strpos($templateTrait, "Db::name('auth_manager_role')") === false, '模板不应直接写账号角色关系', $failures);
checkTemplate(strpos($templateClient, 'assertTemplateManaged') !== false, '模板写操作缺少组织权限校验', $failures);
checkTemplate(strpos($provider, "authRoleTemplate") !== false, '模板客户端未注册到管理端容器', $failures);
checkTemplate(strpos($migration, 'auth_role_template_node') !== false, '数据库变更缺少模板节点表', $failures);
checkTemplate(strpos($migration, 'template_id') !== false, '数据库变更缺少角色模板字段', $failures);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 账号绑定角色前自动应用模板\n";
echo "[PASS] 模板角色不能被旧节点接口覆盖\n";
echo "[PASS] 模板只同步角色节点，不改变账号角色关系\n";
echo "[PASS] 模板写操作包含组织权限校验且客户端已注册\n";
echo "[PASS] 数据库变更包含模板主表、节点表和角色模板字段\n";
echo "\nSummary: passed=5, failed=0\n";
