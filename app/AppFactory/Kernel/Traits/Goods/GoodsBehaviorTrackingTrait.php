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
        if (isset($postData['is_online']) && in_array(intval($postData['is_online']), [1, 2], true)) {
            $where[] = ['is_online', '=', intval($postData['is_online'])];
        }
        // saleDataCollect 会将 create_date 覆盖为 pay_time；这里同时兼容两个参数名。
        $dateRange = $postData['create_date'] ?? $postData['pay_time'] ?? '';
        if ($dateRange) {
            $time = is_array($dateRange) ? array_values($dateRange) : explode('~', $dateRange, 2);
            if (count($time) === 2) {
                $startTime = is_numeric($time[0]) ? intval($time[0]) : strtotime(trim($time[0]));
                $endTime = is_numeric($time[1]) ? intval($time[1]) : strtotime(trim($time[1]));
                if ($startTime !== false && $endTime !== false) {
                    $where[] = [
                        'report_date',
                        'between',
                        [date('Y-m-d', $startTime), date('Y-m-d', $endTime)],
                    ];
                }
            }
        }
        $query = Db::name('goods_behavior_tracking')->where($where);

        return (int)$query->sum('click_count');
    }
}
