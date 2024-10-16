<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'dataUpload' => 'app\command\DataUpload',
        'exportQueue' => 'app\command\ExportQueue',
        'machineReceive' => 'app\command\MachineReceive',
        'time_task' => 'app\command\TimeTask',
        'api' => 'app\command\Api',
        'payment' => 'app\command\Payment',
    ],
];
