<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/11
 * Time: 15:10
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleOrdersRevenueModel extends BaseModel
{
    protected $pk = "sor_id";
    protected $name = "sale_orders_revenue";

//    protected $schema = [
//        "sor_id" => "int",
//        "si_id" => "int",
//        "order_id" => "int",
//        "sod_id" => "int",
//        "m_id" => "int",
//        "machine_name" => "string",
//        "machine_id" => "string",
//        "order_amount" => "float",
//        "sod_amount" => "float",
//        "sod_quantity" => "int",
//        "sod_total_price" => "float",
//        "income_value" => "int",
//        "income_amount" => "float",
//        "refund_status" => "int",
//        "refund_amount" => "float",
//        "beneficiary" => "int",
//        "revenue_type" => "int",
//        "revenue_time" => "int",
//        "status" => "int",
//        "create_time" => "int",
//        "update_time" => "int",
//    ];
}