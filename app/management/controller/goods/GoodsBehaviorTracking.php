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
    protected $field = "gbt.*,g.g_name,g.pic";

    /**
     * 商品行为埋点记录列表
     * 筛选：machine_id(多选逗号分隔)、goods_id(多选逗号分隔)、device_created_at(时间范围~)、goods_name(商品名称模糊)
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;

        // 商品名称筛选（goods表字段，需单独处理前缀，先从postData取出避免getWhere误加gbt.前缀）
        $goodsWhere = [];
        if (!empty($postData['goods_name'])) {
            $goodsWhere[] = ['g.g_name', 'like', '%' . $postData['goods_name'] . '%'];
            unset($postData['goods_name']);
        }

        $where = $this->getWhere($postData, false, [], 'gbt.');

        return $this->app->goodsBehaviorTracking->getTrackingList($where, $pageNum, $this->field, 'gbt.gbt_id desc', $goodsWhere);
    }
}
