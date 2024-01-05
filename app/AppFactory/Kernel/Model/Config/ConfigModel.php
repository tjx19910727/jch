<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:51
 */

namespace app\AppFactory\Kernel\Model\Config;


use app\AppFactory\Kernel\Model\BaseModel;

class ConfigModel extends BaseModel
{
    protected $pk = "config_id";
    protected $name = "config";

    protected $schema = [
        "config_id" => "int",
        "config_name" => "string",
        "config_content" => "string",
        "config_switch" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}