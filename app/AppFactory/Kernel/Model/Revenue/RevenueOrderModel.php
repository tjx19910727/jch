<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueOrderModel extends BaseModel
{
    protected $pk = "ro_id";
    protected $name = "revenue_order";

    protected $schema = [
        "ro_id" => "int",
        "order_id" => "int",
        "sod_id" => "int",
        "g_id" => "int",
        "mg_id" => "int",
        "trade_no" => "string",
        "sp_id" => "int",
        "m_id" => "int",
        "machine_id" => "string",
        "machine_name" => "string",
        "order_amount" => "float",
        "sod_amount" => "float",
        "sod_quantity" => "int",
        "sod_total_price" => "float",
        "rule_mode" => "int",
        "rr_id" => "int",
        "payer_ao_id" => "int",
        "receiver_ao_id" => "int",
        "ra_id" => "int",
        "manager_id" => "int",
        "manager_name" => "string",
        "account_type" => "string",
        "account" => "string",
        "calc_type" => "int",
        "income_value" => "float",
        "income_amount" => "float",
        "refund_amount" => "float",
        "period_key" => "string",
        "period_amount_before" => "float",
        "period_amount_after" => "float",
        "source" => "string",
        "settlement_type" => "int",
        "settlement_days" => "int",
        "planned_revenue_time" => "int",
        "status" => "int",
        "revenue_time" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
