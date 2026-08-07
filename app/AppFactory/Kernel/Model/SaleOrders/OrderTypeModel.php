<?php

namespace app\AppFactory\Kernel\Model\SaleOrders;

use app\AppFactory\Kernel\Model\BaseModel;

class OrderTypeModel extends BaseModel
{
    protected $pk = "ot_id";
    protected $name = "order_type";

    protected $schema = [
        "ot_id" => "int",
        "order_type" => "int",
        "order_type_name" => "string",
        "status" => "int",
        "sort" => "int",
        "remark" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}
