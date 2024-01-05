<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/6
 * Time: 11:38
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityHgModel extends BaseModel
{
    protected $pk = "ah_id";
    protected $name = "activity_hg";

    protected $schema = [
        "ah_id" => "int",
        "store_id" => "int",
        "store_name" => "string",
        "terminal_no" => "string",
        "store_manager" => "int",
        "activity_name" => "string",
        "start_date" => "int",
        "end_date" => "int",
        "days" => "string",
        "week" => "string",
        "desc" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}