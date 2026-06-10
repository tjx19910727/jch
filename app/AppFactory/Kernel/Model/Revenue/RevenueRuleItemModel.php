<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleItemModel extends BaseModel
{
    protected $pk = "rri_id";
    protected $name = "revenue_rule_item";

    protected $schema = [
        "rri_id" => "int",
        "rr_id" => "int",
        "g_id" => "int",
        "receiver_ao_id" => "int",
        "ra_id" => "int",
        "manager_id" => "int",
        "calc_type" => "int",
        "calc_value" => "float",
        "sort" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
