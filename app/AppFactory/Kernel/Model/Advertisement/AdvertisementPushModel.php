<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/6
 * Time: 16:57
 */

namespace app\AppFactory\Kernel\Model\Advertisement;


use app\AppFactory\Kernel\Model\BaseModel;

class AdvertisementPushModel extends BaseModel
{
    protected $pk = "adv_id";
    protected $name = "advertisement_push";

    protected $schema = [
        "adv_id" => "int",
        "adv_title" => "int",
        "res_id" => "int",
        "res_title" => "string",
        "file_path" => "string",
        "duration_time" => "int",
        "total_times" => "int",
        "play_times" => "int",
        "remain_times" => "int",
        "store_id" => "int",
        "store_name" => "string",
        "terminal_no" => "string",
        "start_date" => "int",
        "end_date" => "int",
        "start_time" => "int",
        "end_time" => "int",
        "position" => "int",
        "screen" => "int",
        "screen_full" => "int",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}