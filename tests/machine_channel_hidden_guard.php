<?php

$root = dirname(__DIR__);
$api = file_get_contents($root . '/app/AppFactory/Machine/Receive/ApiClient.php');
$sql = file_get_contents($root . '/文档说明/设备货道隐藏标记数据库变更.sql');

function methodBody($source, $methodName)
{
    $start = strpos($source, 'function ' . $methodName . '(');
    if ($start === false) return '';

    $next = strpos($source, "\n    public function ", $start + 1);
    return $next === false ? substr($source, $start) : substr($source, $start, $next - $start);
}

$machineChannel = methodBody($api, 'machineChannel');
$wcMachineChannel = methodBody($api, 'getWcMCLists');

$machineFilterPos = strpos($machineChannel, "\$where['is_hidden'] = 2;");
$machineQueryPos = strpos($machineChannel, 'getMachineChannelList($where');
$wcFilterPos = strpos($wcMachineChannel, "\$where['is_hidden'] = 2;");
$wcQueryPos = strpos($wcMachineChannel, 'getWcMachineChannelList($where');

$checks = [
    'machine_channel hidden column exists' => strpos($sql, 'ALTER TABLE `machine_channel`') !== false
        && strpos($sql, 'ADD COLUMN `is_hidden` tinyint(1) NOT NULL DEFAULT 2') !== false,
    'wc_machine_channel hidden column exists' => strpos($sql, 'ALTER TABLE `wc_machine_channel`') !== false
        && substr_count($sql, 'ADD COLUMN `is_hidden` tinyint(1) NOT NULL DEFAULT 2') === 2,
    'machine_channel lookup index exists' => strpos($sql, 'ADD INDEX `idx_mid_hidden` (`m_id`, `is_hidden`)') !== false,
    'wc_machine_channel lookup index exists' => strpos($sql, 'ADD INDEX `idx_machine_hidden` (`machine_id`, `is_hidden`)') !== false,
    'getMachineChannel filters before query' => $machineFilterPos !== false
        && $machineQueryPos !== false
        && $machineFilterPos < $machineQueryPos,
    'getWcMCLists filters before paginated query' => $wcFilterPos !== false
        && $wcQueryPos !== false
        && $wcFilterPos < $wcQueryPos,
    'getWcMCLists preserves paginator envelope' => strpos(
        $wcMachineChannel,
        "if (\$pageNum) \$wcMachineChannelLists['data'] = \$wcMachineChannelData;"
    ) !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
