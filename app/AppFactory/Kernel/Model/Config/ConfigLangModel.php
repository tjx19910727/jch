<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 16:35
 */

namespace app\AppFactory\Kernel\Model\Config;


use app\AppFactory\Kernel\Model\BaseModel;

class ConfigLangModel extends BaseModel
{
    protected $pk = "l_id";
    protected $name = "config_lang";
    protected $schema = [
        "l_id" => "int",
        "name" => "string",
        "lang" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}