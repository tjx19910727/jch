<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/25
 * Time: 9:04
 */

namespace app\AppFactory\Kernel\Support\Validate\Machine;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VReport extends SupportValidate
{
    protected $rule = [
        "machine_id" => "require",
        "msgType" => "require",
        "timestamp" => "require",
        "sign" => "require",
    ];

    protected $message = [
        "machine_id.require" => "设备编号不能为空",
        "msgType.require" => "消息类型不能为空",
        "timestamp.require" => "时间戳不能为空",
        "sign.require" => "签名不能为空",
    ];

    protected $scene = [
        "onMessage" => ["machine_id","msgType","timestamp","sign"],
    ];
}