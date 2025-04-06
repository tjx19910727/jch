<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/24
 * Time: 13:50
 */

namespace app\AppFactory\Kernel\Model\Common;


use app\AppFactory\Kernel\Model\BaseModel;

class CityModel extends BaseModel
{
    protected $pk = "city_id";
    protected $name = "city";

    protected $schema = [
        "city_id" => "int",
        "city_pid" => "int",
        "city_title" => "string",
        "city_fullname" => "string",
        "city_code" => "string",
        "city_code_pid" => "int",
        "city_pinyin" => "string",
        "city_level" => "int",
        "city_location" => "string",
    ];
}