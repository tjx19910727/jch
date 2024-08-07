<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 14:31
 */

namespace app\AppFactory\Kernel\Model\Trip;


use app\AppFactory\Kernel\Model\BaseModel;

class TripCityModel extends BaseModel
{
    protected $pk = "tc_id";
    protected $name = "trip_city";
}