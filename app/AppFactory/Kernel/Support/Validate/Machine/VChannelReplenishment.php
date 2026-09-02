<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/1
 * Time: 19:26
 */

namespace app\AppFactory\Kernel\Support\Validate\Machine;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VChannelReplenishment extends SupportValidate
{
    protected $rule = [
        "mc_id" => "require",
        "quantity" => "require",
    ];

    protected $message = [
        "mc_id.require" => "VChannelReplenishment.mc_id_require",
        "quantity.require" => "VChannelReplenishment.quantity_require",
    ];

    protected $scene = [
        "replenishment" => ["mc_id","quantity"],
        "repList" => ["mc_id","quantity"],
    ];
}