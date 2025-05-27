<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:37
 */

namespace app\AppFactory\Kernel\Traits\Earth;


use app\AppFactory\Kernel\Model\Earth\EarthAreaModel;

trait EarthAreaTrait
{
    public function getEarthAreaList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthAreaModel::getList($where,$pageNum,$field,$order);
    }
}