<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 17:33
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineOnlineDetailsModel extends BaseModel
{
    protected $pk = "mod_id";
    protected $name = "machine_online_details";
    protected $createTime = "online_time";

//    protected $schema = [
//        "sod_id" => "int",
//        "store_id" => "int",
//        "store_name" => "string",
//        "terminal_no" => "string",
//        "online_id" => "int",
//        "online_time" => "int",
//        "offline_time" => "int",
//        "client_id" => "string",
//        "heart_time" => "int",
//        "sod_duration" => "int",
//        "d_date" => "int",
//    ];
}