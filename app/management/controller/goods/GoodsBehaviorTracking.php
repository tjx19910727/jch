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
    protected $field = "gbt.gbt_id,gbt.m_id,gbt.machine_id,gbt.goods_id,gbt.is_online,gbt.record_key,gbt.click_count,gbt.cart_add_count,gbt.order_count,gbt.purchase_success_count,gbt.retry_dispense_count,gbt.help_count,gbt.report_date,FROM_UNIXTIME(gbt.device_created_at, '%Y-%m-%d %H:%i:%s') device_created_at,FROM_UNIXTIME(gbt.device_updated_at, '%Y-%m-%d %H:%i:%s') device_updated_at,gbt.active_orders,gbt.created_at,gbt.updated_at,IF(gbt.is_online = 1, wg.g_name, g.g_name) g_name,IF(gbt.is_online = 1, wg.pic, g.pic) pic";

    /**
     * 商品行为埋点记录列表
     * 筛选：machine_id(多选逗号分隔)、goods_id(多选逗号分隔)、device_created_at(时间范围~)、goods_name(商品名称模糊)
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;

        // 商品名称来自 goods/wc_goods_local，单独处理以避免 getWhere 误加 gbt. 前缀。
        $goodsName = '';
        if (!empty($postData['goods_name'])) {
            $goodsName = trim(strval($postData['goods_name']));
            unset($postData['goods_name']);
        }

        $where = $this->getWhere($postData, false, [], 'gbt.');

        return $this->app->goodsBehaviorTracking->getTrackingList($where, $pageNum, $this->field, 'gbt.gbt_id desc', $goodsName);
    }
}
