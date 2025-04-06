<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/7
 * Time: 10:03
 */

namespace app\management\validate;


class VSaleOrders extends VCommon
{
    protected $rule = [
        "order_id" => "require",
        "refund" => "require",
    ];

    protected $message = [
        "order_id.require" => "VSaleOrders.order_id_require",
        "refund.require" => "VSaleOrders.refund_require",
    ];

    protected $scene = [
        "refund" => ["order_id","refund"],
    ];
}