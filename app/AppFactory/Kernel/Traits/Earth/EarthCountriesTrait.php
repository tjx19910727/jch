<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Earth;

use app\AppFactory\Kernel\Model\Earth\EarthCountriesModel;

trait EarthCountriesTrait
{
    public function getEarthCountriesValue($where,$value)
    {
        return EarthCountriesModel::getFieldValue($where,$value);
    }

    public function getEarthCountriesFind($where,$field = "*")
    {
        return EarthCountriesModel::getFind($where,$field);
    }

    public function getEarthCountriesList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthCountriesModel::getList($where,$pageNum,$field,$order);
    }
}