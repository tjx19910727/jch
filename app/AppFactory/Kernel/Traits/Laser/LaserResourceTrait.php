<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/28
 * Time: 14:31
 */

namespace app\AppFactory\Kernel\Traits\Laser;


use app\AppFactory\Kernel\Model\Laser\LaserResourceModel;

trait LaserResourceTrait
{
    public function getLaserResourceList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return LaserResourceModel::getList($where, $pageNum, $field, $order);
    }

    public function getLaserResourceFind($where, $field = "*", $order = "")
    {
        return LaserResourceModel::getFind($where, $field, $order);
    }

    public function addLaserResource($insert)
    {
        return LaserResourceModel::insertOneGetId($insert);
    }

    public function updateLaserResource($update, $where = [], $field = [])
    {
        return LaserResourceModel::update($update, $where, $field);
    }

    public function delLaserResource($where)
    {
        return LaserResourceModel::whereDel($where);
    }
}