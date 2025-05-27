<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 14:47
 */

namespace app\AppFactory\Kernel\Model\Earth;


use app\AppFactory\Kernel\Model\BaseModel;

class EarthTimezoneModel extends BaseModel
{
    protected $pk = "t_id";
    protected $name = "earth_timezone";
    protected $schema = [
        "t_id" => "int",
        "UTC" => "string",
        "time_zone" => "string",
        "country" => "string",
        "main_cities" => "string",
    ];
}