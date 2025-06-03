<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Earth;

use app\AppFactory\Kernel\Model\Earth\EarthStatesModel;

trait EarthStatesTrait
{
    public function getEarthStatesColumn($where,$column,$key)
    {
        return EarthStatesModel::getColumn($where,$column,$key);
    }

    public function getEarthStatesValue($where,$value)
    {
        return EarthStatesModel::getFieldValue($where,$value);
    }

    public function getEarthStatesFind($where,$field = "*")
    {
        return EarthStatesModel::getFind($where,$field);
    }

    public function getEarthStatesList($where,$pageNum = 0,$field = "*",$order ="")
    {
        return EarthStatesModel::getList($where,$pageNum,$field,$order);
    }
}