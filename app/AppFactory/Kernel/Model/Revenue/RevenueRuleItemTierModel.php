<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleItemTierModel extends BaseModel
{
    protected $pk = "rrit_id";
    protected $name = "revenue_rule_item_tier";

    protected $schema = [
        "rrit_id" => "int",
        "rri_id" => "int",
        "threshold_min" => "float",
        "threshold_max" => "float",
        "calc_value" => "float",
        "sort" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
