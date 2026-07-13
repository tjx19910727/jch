<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleConfigModel extends BaseModel
{
    protected $pk = "rrcfg_id";
    protected $name = "revenue_rule_config";

    protected $schema = [
        "rrcfg_id" => "int",
        "config_name" => "string",
        "rule_mode" => "int",
        "base_type" => "int",
        "turnover_type" => "int",
        "tier_calc_mode" => "int",
        "settlement_type" => "int",
        "settlement_days" => "int",
        "coupon_id" => "int",
        "cost_assume" => "int",
        "trigger_pay_types" => "string",
        "receiver_config" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
