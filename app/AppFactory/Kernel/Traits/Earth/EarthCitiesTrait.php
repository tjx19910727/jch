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
    public function getEarthCitiesList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthCitiesModel::getList($where,$pageNum,$field,$order);
    }
}