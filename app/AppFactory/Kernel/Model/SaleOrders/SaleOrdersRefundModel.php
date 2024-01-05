<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/14
 * Time: 14:01
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleOrdersRefundModel extends BaseModel
{
    protected $pk = "sor_id";
    protected $name = "sale_orders_refund";

    protected $schema = [
        "sor_id" => "int",
        "order_id" => "int",
        "sod_id" => "int",
        "ss_id" => "int",
        "goods_id" => "int",
        "wg_id" => "int",
        "refund_trade_no" => "string",
        "refund_no" => "string",
        "refund_amount" => "float",
        "refund_quantity" => "int",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}