<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 * Time: 0:00
 */

namespace app\AppFactory\Kernel\Traits\Goods;

use app\AppFactory\Kernel\Model\Goods\GoodsBehaviorTrackingModel;
use think\facade\Db;

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

    /**
     * 从 goods_behavior_tracking 表按条件汇总 click_count
     * @param array $where  业务侧的完整 $where 条件
     * @return int
     */
    public function getBehaviorClickSum($where)
    {
        $query = Db::name('goods_behavior_tracking')->alias('gbt')
            ->join('machine m', 'm.m_id = gbt.m_id')
            ->where($where);

        return (int)$query->sum('gbt.click_count');
    }
}
