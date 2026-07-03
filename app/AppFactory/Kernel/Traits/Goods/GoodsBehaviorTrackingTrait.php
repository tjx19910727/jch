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
    public function getBehaviorClickSum($postData)
    {
        $where = [];
        if(isset($postData['g_id']) && $postData['g_id']){
            $where[] = ['goods_id', 'in', is_array($postData['g_id']) ? $postData['g_id'] : explode(',', $postData['g_id'])];
        }
        if(isset($postData['create_date']) && $postData['create_date']){
            $time = is_array($postData['create_date']) ? $postData['create_date'] : explode('~', $postData['create_date']);
            if(count($time) == 2){
                $where[] = ['device_created_at', 'between', [strtotime($time[0]), strtotime($time[1])]];
            }
        }
        $query = Db::name('goods_behavior_tracking')->where($where);

        return (int)$query->sum('click_count');
    }
}
