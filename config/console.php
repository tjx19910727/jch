<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'dataUpload' => 'app\command\DataUpload',
        'exportQueue' => 'app\command\ExportQueue',
        'asyncTaskQueue' => 'app\command\AsyncTaskQueue',
        'wcGoodsSyncQueue' => 'app\command\WcGoodsSyncQueue',
        'machineReceive' => 'app\command\MachineReceive',
        'time_task' => 'app\command\TimeTask',
        'api' => 'app\command\Api',
        'third_party_sync' => 'app\command\ThirdPartySync',
        'payment' => 'app\command\Payment',
        'visual_screen_ws' => 'app\command\VisualScreenWs',
    ],
];
