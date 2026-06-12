<?php

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Management/Auth/AuthRoleTemplateClient.php');
$controller = file_get_contents($root . '/app/management/controller/auth/AuthRoleTemplate.php');
$nodeController = file_get_contents($root . '/app/management/controller/auth/AuthNode.php');
$validator = file_get_contents($root . '/app/management/validate/VAuth.php');
$model = file_get_contents($root . '/app/AppFactory/Kernel/Model/Auth/AuthNodeModel.php');
$sql = file_get_contents($root . '/文档说明/角色权限模板组合授权数据库变更.sql');
$ui = file_get_contents($root . '/文档说明/角色权限模板顶级导航批量授权UI.html');

$checks = [
    'permission action schema exists' => strpos($sql, 'ADD COLUMN `permission_action`') !== false
        && strpos($model, '"permission_action" => "string"') !== false,
    'permission action is manageable' => strpos($nodeController, 'permission_action') !== false
        && strpos($validator, '"permission_action" => "in:menu,query,export,manage"') !== false,
    'top navigation tree endpoint exists' => strpos($controller, 'function getTopNavigationNodes()') !== false
        && strpos($client, 'function getTopNavigationNodes(array $excludedNodeIds = [])') !== false,
    'combined save endpoint exists' => strpos($controller, 'function saveTopNavigationNodes()') !== false
        && strpos($client, 'function saveTopNavigationNodes($data, array $excludedNodeIds = [])') !== false,
    'query and export are independent' => strpos($client, '$queryEnabled =') !== false
        && strpos($client, '$exportEnabled =') !== false
        && strpos($client, "(\$queryEnabled && \$action === 'query')") !== false
        && strpos($client, "(\$exportEnabled && \$action === 'export')") !== false,
    'all permission includes all descendants' => strpos($client, "\$action === 'menu' || \$allEnabled") !== false,
    'only top navigation can be submitted' => strpos($client, "intval(\$nodeMap[\$topNodeId]['pid']) !== 0") !== false,
    'hidden system nodes are excluded from read and save' => substr_count($client, "if (\$excludedNodeIds) \$query->where('node_id', 'not in', \$excludedNodeIds);") === 2
        && strpos($controller, 'getExcludedTemplateNodeIds()') !== false,
    'top navigation is always included' => strpos($client, '$nodeList[$topNodeId] = $dType;') !== false,
    'duplicate top navigation is rejected' => strpos($client, '顶级导航不能重复配置') !== false,
    'explicit empty top navigation list can clear template' => strpos($client, "array_key_exists('top_navigation_list', \$data)") !== false
        && strpos($client, "\$topNavigationList = json2arr(\$data['top_navigation_list'] ?? []);") !== false,
    'save replaces template nodes only' => strpos($client, 'replaceAuthRoleTemplateNodes') !== false
        && strpos($client, "Db::name('auth_role_node')") === false,
    'validation scene exists' => strpos($validator, '"AuthRoleTemplateTopNavigationNodes"') !== false,
    'api nodes included in migration' => strpos($sql, '/management/auth.auth_role_template/getTopNavigationNodes') !== false
        && strpos($sql, '/management/auth.auth_role_template/saveTopNavigationNodes') !== false,
    'ui supports combined query and export' => strpos($ui, "setPermission(\${index}, 'query'") !== false
        && strpos($ui, "setPermission(\${index}, 'export'") !== false
        && strpos($ui, 'query_enabled: n.query ? 1 : 0') !== false
        && strpos($ui, 'export_enabled: n.export ? 1 : 0') !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
