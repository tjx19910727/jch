<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:22
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineGroupLangModel extends BaseModel
{
    protected $pk = "mgl_id";
    protected $name = "machine_group_lang";
    protected $schema = [
        "mgl_id" => "int",
        "mg_id" => "int",
        "mg_name" => "string",
        "desc" => "string",
        "lang" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}