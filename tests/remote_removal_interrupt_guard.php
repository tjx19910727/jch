<?php

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/management/controller/machine/MachineChannel.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Machine/MachineChannelClient.php');
$receiver = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Machine/MachineTrait.php');
$sql = file_get_contents($root . '/文档说明/remote_removal_interrupt.sql');

$remoteMethodPosition = strpos($controller, 'public function remoteRemoval()');
$interruptMethodPosition = strpos($controller, 'public function interruptRemoteRemoval()');
$exportMethodPosition = strpos($controller, 'public function exportByShelfLevel()');

$checks = [
    'interrupt controller method follows remoteRemoval' => $remoteMethodPosition !== false
        && $interruptMethodPosition !== false
        && $exportMethodPosition !== false
        && $remoteMethodPosition < $interruptMethodPosition
        && $interruptMethodPosition < $exportMethodPosition,
    'button statuses are integer zero and one' => strpos($client, 'REMOTE_REMOVAL_STATUS_REMOTE = 0') !== false
        && strpos($client, 'REMOTE_REMOVAL_STATUS_INTERRUPT = 1') !== false,
    'channel list returns button status field' => strpos($client, 'remote_removal_status') !== false,
    'channel list limits logs to thirty minutes' => strpos($client, 'time() - self::REMOTE_REMOVAL_WAIT_REPORT_SECONDS') !== false,
    'pending report blocks retry for thirty minutes' => strpos($client, 'REMOTE_REMOVAL_WAIT_REPORT_SECONDS = 1800') !== false
        && strpos($client, '上次指令未执行完毕，请稍后再试') !== false,
    'interrupt command keeps legacy parameters' => substr_count($client, 'interruptRemoteRemoval') >= 2
        && substr_count($client, 'manager_id') >= 2,
    'device report time restores remote button status' => strpos($receiver, 'reported_at') !== false
        && strpos($receiver, 'time()') !== false,
    'database change stores interrupt time only' => strpos($sql, 'interrupted_at') !== false
        && strpos($sql, 'operation_status') === false,
    'button state is derived without operation status storage' => strpos($client, 'operation_status') === false
        && strpos($receiver, 'operation_status') === false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf('[%s] %s' . PHP_EOL, $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
