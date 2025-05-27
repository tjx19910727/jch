<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/14
 * Time: 15:30
 */

namespace app\AppFactory\Kernel\Support\Validate\SaleOrders;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VSaleOrdersRefund extends SupportValidate
{
    protected $rule = [
        "sod_id" => "require",
        "quantity" => "require",
    ];

    protected $message = [
        "sod_id.require" => "请选择需要退款的商品",
        "quantity.require" => "请确认退款的商品数量",
    ];

    protected $scene = [
        "refund" => ["sod_id","quantity"],
    ];
}