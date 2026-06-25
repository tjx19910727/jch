<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 * Time: 0:00
 */

namespace app\management\controller\goods;

use app\management\controller\Common;

class GoodsBehaviorTracking extends Common
{
    protected $field = "gbt_id,m_id,machine_id,goods_id,record_key,click_count,cart_add_count,order_count,purchase_success_count,retry_dispense_count,help_count,report_date,device_created_at,device_updated_at,active_orders,created_at,updated_at";

    /**
     * 商品行为埋点记录列表
     * 筛选：machine_id(多选逗号分隔)、goods_id(多选逗号分隔)、report_date(时间范围~)
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData);

        if (!empty($postData['machine_id'])) {
            $where[] = ['machine_id', 'in', $postData['machine_id']];
        }
        if (!empty($postData['goods_id'])) {
            $where[] = ['goods_id', 'in', $postData['goods_id']];
        }
        if (!empty($postData['report_date'])) {
            $dates = explode('~', $postData['report_date']);
            if (count($dates) === 2) {
                $where[] = ['report_date', 'between', [trim($dates[0]), trim($dates[1])]];
            } else {
                $where[] = ['report_date', '=', trim($postData['report_date'])];
            }
        }

        return $this->app->goodsBehaviorTracking->getTrackingList($where, $pageNum, $this->field, 'gbt_id desc');
    }
}
