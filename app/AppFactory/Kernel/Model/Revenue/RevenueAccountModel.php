<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenueAccountModel extends BaseModel
{
    protected $pk = "ra_id";
    protected $name = "revenue_account";

    protected $schema = [
        "ra_id" => "int",
        "ao_id" => "int",
        "manager_id" => "int",
        "account_name" => "string",
        "account" => "string",
        "account_type" => "string",
        "bill_account" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
