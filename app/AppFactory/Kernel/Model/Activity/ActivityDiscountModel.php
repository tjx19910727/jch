<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:07
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityDiscountModel extends BaseModel
{
    protected $pk = "ad_id";
    protected $name = "activity_discount";

    protected $schema = [
        "ad_id" => "int",
        "store_id" => "int",
        "store_name" => "string",
        "terminal_no" => "string",
        "store_manager" => "int",
        "activity_name" => "string",
        "start_date" => "int",
        "end_date" => "int",
        "days" => "string",
        "week" => "string",
        "discount" => "float",
        "desc" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}