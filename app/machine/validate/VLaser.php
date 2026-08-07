<?php

namespace app\machine\validate;

class VLaser extends VCommon
{
    protected $rule = [
        'machine_id' => 'require',
        'msg_id' => 'require',
        'timestamp' => 'require|integer',
        'sign' => 'require',
    ];

    protected $message = [
        'machine_id.require' => '设备编号不能为空',
        'msg_id.require' => '消息ID不能为空',
        'timestamp.require' => '时间戳不能为空',
        'timestamp.integer' => '时间戳必须为整数',
        'sign.require' => '签名不能为空',
    ];

    protected $scene = [
        'deviceRequest' => ['machine_id', 'msg_id', 'timestamp', 'sign'],
        'h5Request' => ['sign'],
    ];
}
