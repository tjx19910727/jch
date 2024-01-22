<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:44
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthRegionsModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "earth_regions";
    protected $schema = [
        "id" => "int",
        "city_id" => "int",
        "code" => "string",
        "name" => "string",
        "cname" => "string",
        "lower_name" => "string",
        "code_full" => "string",
    ];
}