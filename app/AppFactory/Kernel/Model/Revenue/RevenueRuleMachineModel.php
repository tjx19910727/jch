<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleMachineModel extends BaseModel
{
    protected $pk = "rrm_id";
    protected $name = "revenue_rule_machine";

    protected $schema = [
        "rrm_id" => "int",
        "rr_id" => "int",
        "m_id" => "int",
        "ao_id" => "int",
        "sort" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
