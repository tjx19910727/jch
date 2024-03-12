<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'dataUpload' => 'app\command\DataUpload',
        'machineReceive' => 'app\command\MachineReceive',
        'time_task' => 'app\command\TimeTask',
    ],
];
