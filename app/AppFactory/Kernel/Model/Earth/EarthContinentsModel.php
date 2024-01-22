<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:38
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthContinentsModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "earth_continents";
    protected $schema = [
        "id" => "int",
        "name" => "string",
        "cname" => "string",
        "lower_name" => "string",
    ];
}