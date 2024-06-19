<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:24
 */

namespace app\AppFactory\Kernel\Model\Config;


use app\AppFactory\Kernel\Model\BaseModel;

class ConfigApiModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "config_api";
}