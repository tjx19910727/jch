<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:42
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthCountriesModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "earth_countries";
    protected $schema = [
        "id" => "int",
        "continent_id" => "int",
        "code" => "string",
        "name" => "string",
        "full_name" => "string",
        "cname" => "string",
        "full_cname" => "string",
        "lower_name" => "string",
        "remark" => "string",
    ];
}