<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/17
 * Time: 16:58
 */

namespace app\AppFactory\Kernel\Model\Config;


use app\AppFactory\Kernel\Model\BaseModel;

class ConfigPerformanceModel extends BaseModel
{
    protected $pk = "cp_id";
    protected $name = "config_performance";

    protected $schema = [
        'cp_id' => "int",
        'name' => "string",
        'field' => "string",
        'lang' => "string",
        'creator' => "int",
        'create_time' => "int",
        'update_id' => "int",
        'update_time' => "int",
    ];
}