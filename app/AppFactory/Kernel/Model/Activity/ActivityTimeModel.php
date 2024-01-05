<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:20
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityTimeModel extends BaseModel
{
    protected $pk = "at_id";
    protected $name = "activity_time";

    protected $schema = [
        "at_id" => "int",
        "a_id" => "int",
        "a_type" => "int",
        "start_time" => "int",
        "end_time" => "int",
    ];
}