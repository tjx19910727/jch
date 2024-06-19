<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:37
 */

namespace app\AppFactory\Kernel\Traits\Earth;


use app\AppFactory\Kernel\Model\Earth\EarthCitiesModel;

trait EarthCitiesTrait
{
    public function getEarthCitiesValue($where,$value)
    {
        return EarthCitiesModel::getFieldValue($where,$value);
    }
    public function getEarthCitiesFind($where,$field = "*")
    {
        return EarthCitiesModel::getFind($where,$field);
    }

    public function getEarthCitiesList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthCitiesModel::getList($where,$pageNum,$field,$order);
    }
}