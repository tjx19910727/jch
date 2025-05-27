<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/1
 * Time: 15:10
 */

namespace app\AppFactory\Kernel\Support\Validate\Machine;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VChannel extends SupportValidate
{
    protected $rule = [
        "channel_code" => "require",
        "channel_position" => "require",
    ];

    protected $message = [
        "channel_code.require" => "VChannel.channel_code_require",
        "channel_position.require" => "VChannel.channel_position_require",
    ];

    protected $scene = [
        "subChannel" => ['channel_code',"channel_position"],
    ];
}