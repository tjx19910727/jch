<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleCouponScopeModel extends BaseModel
{
    protected $pk = "rrcs_id";
    protected $name = "revenue_rule_coupon_scope";

    protected $schema = [
        "rrcs_id" => "int",
        "rrc_id" => "int",
        "m_id" => "int",
        "machine_id" => "string",
        "g_id" => "int",
        "mg_id" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}

