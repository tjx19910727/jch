<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Earth;

use app\AppFactory\Kernel\Model\Earth\EarthRegionsModel;

trait EarthRegionsTrait
{
    public function getEarthRegionsValue($where,$value)
    {
        return EarthRegionsModel::getFieldValue($where,$value);
    }
    public function getEarthRegionsFind($where,$field = "*")
    {
        return EarthRegionsModel::getFind($where,$field);
    }

    public function getEarthRegionsList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthRegionsModel::getList($where,$pageNum,$field,$order);
    }
}