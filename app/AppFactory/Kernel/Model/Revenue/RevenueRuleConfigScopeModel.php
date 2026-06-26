<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleConfigScopeModel extends BaseModel
{
    protected $pk = "rrcs_id";
    protected $name = "revenue_rule_config_scope";

    protected $schema = [
        "rrcs_id" => "int",
        "rrcfg_id" => "int",
        "m_id" => "int",
        "machine_id" => "string",
        "ao_id" => "int",
        "g_id" => "int",
        "mg_id" => "int",
        "sort" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
