<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:46
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthStatesModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "earth_states";
    protected $schema = [
        "id" => "int",
        "country_id" => "int",
        "code" => "string",
        "name" => "string",
        "cname" => "string",
        "lower_name" => "string",
        "code_full" => "string",
        "area_id" => "int",
    ];
}