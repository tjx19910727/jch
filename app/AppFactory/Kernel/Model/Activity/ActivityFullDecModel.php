<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 17:31
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityFullDecModel extends BaseModel
{
    protected $pk = "afd_id";
    protected $name = "activity_full_dec";

    protected $schema = [
        "afd_id" => "int",
        "store_id" => "int",
        "store_name" => "string",
        "terminal_no" => "string",
        "store_manager" => "int",
        "activity_name" => "string",
        "start_date" => "int",
        "end_date" => "int",
        "days" => "string",
        "week" => "string",
        "full_type" => "int",
        "full" => "float",
        "dec_type" => "int",
        "dec" => "float",
        "desc" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}