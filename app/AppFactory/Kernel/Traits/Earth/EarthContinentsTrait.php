<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:37
 */

namespace app\AppFactory\Kernel\Traits\Earth;

use app\AppFactory\Kernel\Model\Earth\EarthContinentsModel;

trait EarthContinentsTrait
{
    public function getEarthContinentsList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthContinentsModel::getList($where,$pageNum,$field,$order);
    }
}