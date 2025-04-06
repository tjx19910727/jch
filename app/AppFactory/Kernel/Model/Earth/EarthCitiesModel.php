<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:37
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthCitiesModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "earth_cities";
    protected $schema = [
        "id" => "int",
        "state_id" => "int",
        "code" => "string",
        "name" => "string",
        "cname" => "string",
        "lower_name" => "string",
        "code_full" => "string",
    ];
}