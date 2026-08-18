<?php

namespace app\management\validate\Machine;

use app\management\validate\VCommon;

class VMachineVideoRecord extends VCommon
{
    protected $rule = [
        'machine_id' => 'require|alphaDash',
        'record_seconds' => 'require|integer|between:1,300',
        'log_id' => 'require|integer|gt:0',
    ];

    protected $message = [
        'machine_id.require' => '设备编号不能为空',
        'machine_id.alphaDash' => '设备编号格式错误',
        'record_seconds.require' => '录制秒数不能为空',
        'record_seconds.integer' => '录制秒数必须为整数',
        'record_seconds.between' => '录制秒数必须在1到300秒之间',
        'log_id.require' => '录制日志ID不能为空',
        'log_id.integer' => '录制日志ID必须为整数',
        'log_id.gt' => '录制日志ID必须大于0',
    ];

    protected $scene = [
        'recordVideo' => ['machine_id', 'record_seconds'],
        'getVideo' => ['log_id'],
    ];
}
