<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 15:53
 */

namespace app\AppFactory\Kernel\Model\Trip;


use app\AppFactory\Kernel\Model\BaseModel;

class TripMultipleModel extends BaseModel
{
    protected $pk = "tm_id";
    protected $name = "trip_multiple";
}