<?php

namespace app\AppFactory\Kernel\Model\Payment;

use app\AppFactory\Kernel\Model\BaseModel;

class PayTypeModel extends BaseModel
{
    protected $pk = "pt_id";
    protected $name = "pay_type";

    protected $schema = [
        "pt_id" => "int",
        "pay_type" => "int",
        "pay_type_name" => "string",
        "pay_scene" => "int",
        "status" => "int",
        "sort" => "int",
        "remark" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
