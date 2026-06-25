<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 * Time: 0:00
 */

namespace app\AppFactory\Kernel\Traits\Goods;

use app\AppFactory\Kernel\Model\Goods\GoodsBehaviorTrackingModel;

trait GoodsBehaviorTrackingTrait
{
    public function getGoodsBehaviorTrackingCount($where)
    {
        return GoodsBehaviorTrackingModel::getCount($where, "gbt_id");
    }

    public function getGoodsBehaviorTrackingFind($where, $field = "*", $order = "")
    {
        return GoodsBehaviorTrackingModel::getFind($where, $field, $order);
    }

    public function getGoodsBehaviorTrackingList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return GoodsBehaviorTrackingModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addGoodsBehaviorTracking($insert)
    {
        return GoodsBehaviorTrackingModel::insertOneGetId($insert);
    }

    public function updateGoodsBehaviorTracking($update, $where = [], $field = [])
    {
        return GoodsBehaviorTrackingModel::update($update, $where, $field);
    }

    public function delGoodsBehaviorTracking($where)
    {
        return GoodsBehaviorTrackingModel::whereDel($where);
    }
}
