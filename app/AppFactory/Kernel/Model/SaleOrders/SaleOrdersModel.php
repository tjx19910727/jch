<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 9:50
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleOrdersModel extends BaseModel
{
    protected $pk = "order_id";
    protected $name = "sale_orders";
    protected $createTime = "create_time";

    protected $schema = [
        "order_id" => "int",
        "trade_no" => "string",
        "mch_no" => "string",
        "user_id" => "int",
        "user_name" => "string",
        "store_id" => "int",
        "store_name" => "string",
        "store_manager" => "int",
        "terminal_no" => "string",
        "order_type" => "int",
        "supplementary_payment" => "int",
        "payment_status" => "int",
        "payment_type" => "int",
        "payment_method" => "int",
        "payment_time" => "int",
        "payment_code" => "string",
        "refund_status" => "int",
        "cost_price" => "float",
        "total_price" => "float",
        "total_quantity" => "int",
        "pickup_time" => "int",
        "remark" => "string",
        "create_time" => "int",
        "sp_id" => "int",
    ];

}