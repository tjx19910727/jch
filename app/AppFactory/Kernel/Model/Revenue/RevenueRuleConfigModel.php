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
        "coupon_code" => "string",
        "discount_type" => "int",
        "discount_value" => "float",
        "use_limit" => "int",
        "used_count" => "int",
        "remain_count" => "int",
        "expire_time" => "int",
        "receiver_config" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
