<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenuePayeeConfigModel extends BaseModel
{
    protected $pk = "rpcfg_id";
    protected $name = "revenue_payee_config";

    protected $schema = [
        "rpcfg_id" => "int",
        "sp_id" => "int",
        "payee_type" => "int",
        "ao_id" => "int",
        "default_ra_id" => "int",
        "default_manager_id" => "int",
        "enable_revenue" => "int",
        "settlement_type" => "int",
        "settlement_days" => "int",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
