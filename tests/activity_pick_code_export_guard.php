<?php

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/management/controller/activity/ActivityPickCode.php');
$validator = file_get_contents($root . '/app/management/validate/Activity/VActivityPickCode.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Activity/ActivityPickCodeClient.php');
$sql = file_get_contents($root . '/文档说明/取货码导出权限节点补全.sql');
$failures = [];

if (substr_count($controller, "\$this->validate(\$postData, \$this->validatePath . 'export')") !== 2
    || strpos($validator, '"export" => ["id"]') === false) {
    $failures[] = '取货码导出接口缺少活动ID校验';
}
if (strpos($client, 'protected function getExportActivities($ids)') === false
    || strpos($client, "explode(',', (string)\$ids)") === false
    || substr_count($client, "[['ap_id', 'in', \$activityIds]") < 2) {
    $failures[] = '取货码导出接口没有兼容逗号分隔的多个活动ID';
}
if (strpos($client, '"pick_name" => "活动名称"') === false
    || strpos($client, 'protected function appendActivityNames(array $list, array $activities)') === false) {
    $failures[] = '多活动取货码导出没有标识活动名称';
}
foreach ([
    '/management/activity.activity_pick_code/exportCode',
    '/management/activity.activity_pick_code/exportUsedList',
] as $url) {
    if (strpos($sql, $url) === false) {
        $failures[] = "权限补全 SQL 缺少 {$url}";
    }
}
if (strpos($sql, 'FROM auth_role_node source_role') === false
    || strpos($sql, "source_node.url = '/management/activity.activity_pick_code/getList'") === false) {
    $failures[] = '权限补全 SQL 未继承取货码列表节点角色授权';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 取货码导出接口校验活动ID\n";
echo "[PASS] 取货码导出接口支持单个、逗号分隔和数组活动ID\n";
echo "[PASS] 多活动取货码导出包含活动名称\n";
echo "[PASS] 权限补全SQL覆盖两个导出接口并继承列表节点授权\n";
echo "\nSummary: passed=4, failed=0\n";
