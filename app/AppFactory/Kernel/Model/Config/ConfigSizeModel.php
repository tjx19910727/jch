<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 11:06
 */

namespace app\AppFactory\Kernel\Model\Config;


use app\AppFactory\Kernel\Model\BaseModel;

class ConfigSizeModel extends BaseModel
{
    protected $pk = "s_id";
    protected $name = "config_size";

    protected $schema =  [
        "s_id" => "int",
        "label" => "string",
        "length" => "int",
        "width" => "int",
        "type" => "int",
    ];
}