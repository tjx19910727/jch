<?php

namespace app\AppFactory\Kernel\Model\Revenue;

use app\AppFactory\Kernel\Model\BaseModel;

class RevenuePayChannelModel extends BaseModel
{
    protected $pk = "rpc_id";
    protected $name = "revenue_pay_channel";

    protected $schema = [
        "rpc_id" => "int",
        "pay_type" => "int",
        "payee_type" => "int",
        "channel_name" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
