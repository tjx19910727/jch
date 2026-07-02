<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueOrderRefundModel extends BaseModel
{
    protected $pk = "ror_id";
    protected $name = "revenue_order_refund";

    protected $schema = [
        "ror_id" => "int",
        "ro_id" => "int",
        "sor_id" => "int",
        "order_id" => "int",
        "sod_id" => "int",
        "refund_trade_no" => "string",
        "trade_no" => "string",
        "manager_id" => "int",
        "manager_name" => "string",
        "account_type" => "string",
        "account" => "string",
        "refund_amount" => "float",
        "refund_quantity" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
