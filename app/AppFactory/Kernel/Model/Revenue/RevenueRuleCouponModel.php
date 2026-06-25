<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueRuleCouponModel extends BaseModel
{
    protected $pk = "rrc_id";
    protected $name = "revenue_rule_coupon";

    protected $schema = [
        "rrc_id" => "int",
        "rr_id" => "int",
        "coupon_code" => "string",
        "discount_type" => "int",
        "discount_value" => "float",
        "use_limit" => "int",
        "used_count" => "int",
        "remain_count" => "int",
        "expire_time" => "int",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
