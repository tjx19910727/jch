<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/1
 * Time: 15:10
 */

namespace app\AppFactory\Kernel\Support\Validate\Machine;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VMachineGoods extends SupportValidate
{
    protected $rule = [
        "mg_id" => "require",
        "g_id" => "require",
    ];

    protected $message = [
        "mg_id.require" => "VChannel.mg_id_require",
        "g_id.require" => "VChannel.g_id_require",
    ];

    protected $scene = [
        "subMachineGoods" => ['mg_id',"g_id"],
    ];
}