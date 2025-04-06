<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:39
 */

namespace app\AppFactory\Kernel\Traits\Earth;

use app\AppFactory\Kernel\Model\Earth\EarthTimezoneModel;

trait EarthTimezoneTrait
{
    public function getEarthTimezoneList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthTimezoneModel::getList($where,$pageNum,$field,$order);
    }
}