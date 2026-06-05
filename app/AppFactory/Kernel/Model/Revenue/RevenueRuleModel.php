<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleModel extends BaseModel
{
    protected $pk = "rr_id";
    protected $name = "revenue_rule";

    protected $schema = [
        "rr_id" => "int",
        "rule_name" => "string",
        "rule_mode" => "int",
        "payer_ao_id" => "int",
        "base_type" => "int",
        "turnover_type" => "int",
        "tier_calc_mode" => "int",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
