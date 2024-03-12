<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 17:26
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineOnlineModel extends BaseModel
{
    protected $pk = "online_id";
    protected $name = "machine_online";

//    protected $schema = [
//        "online_id" => "int",
//        "store_id" => "int",
//        "store_name" => "string",
//        "store_manager" => "int",
//        "terminal_no" => "string",
//        "online_date" => "int",
//        "duration" => "int",
//        "create_time" => "int",
//    ];
}