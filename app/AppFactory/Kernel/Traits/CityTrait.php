<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/24
 * Time: 13:49
 */

namespace app\AppFactory\Kernel\Traits;


use app\AppFactory\Kernel\Model\Common\CityModel;

trait CityTrait
{
    public function getCityFind($where,$field = "*",$order = "")
    {
        return CityModel::getFind($where,$field,$order);
    }

    public function getCityList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return CityModel::getList($where,$pageNum,$field,$order);
    }

    public function getCityValue($where,$value)
    {
        return CityModel::getFieldValue($where,$value);
    }
}