<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:35
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthAreaModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "earth_area";

    protected $schema = [
        "id" => "int",
        "country_id" => "int",
        "code" => "int",
        "name" => "string",
        "cname" => "string",
        "lower_name" => "string",
    ];
}