<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 11:42
 */

namespace app\AppFactory\Kernel\Model\Config;


use app\AppFactory\Kernel\Model\BaseModel;

class ConfigSceneLangModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "config_scene_lang";
    protected $schema = [
        "id" => "int",
        "cs_id" => "int",
        "name" => "string",
        "desc" => "string",
        "lang" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",

    ];
}