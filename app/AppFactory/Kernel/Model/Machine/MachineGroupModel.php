<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:20
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineGroupModel extends BaseModel
{
    protected $pk = "mg_id";
    protected $name = "machine_group";

//    protected $schema = [
//        "mg_id" => "int",
//        "mg_name" => "string",
//        "pid" => "int",
//        "desc" => "string",
//        "sort" => "int",
//        "status" => "int",
//        "creator" => "int",
//        "create_time" => "int",
//        "update_id" => "int",
//        "update_time" => "int",
//    ];
}