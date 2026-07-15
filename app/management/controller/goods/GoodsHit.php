<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 20:23
 */

namespace app\management\controller\goods;


use app\management\controller\Common;

class GoodsHit extends Common
{

    protected $field = "g_id,g_name,sku, count(g_id) hits";

    /**
     * 获取商品点击统计报表
     * @return array|string
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData);
        return $this->app->goodsHit->getTotalListV2($where,$pageNum,$this->field,'g_id desc');
    }

    /**
     * 分组统计一种商品的设备点击记录
     * @return array|string
     */
    public function getHitList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->goodsHit->getHitListV2($where,$postData['pageNum'] ?? 0,'machine_id,machine_name,max(create_time) create_time,count(g_id) hits','','','g_id,m_id');
    }

    public function exportBySku()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->goodsHit->exportV2($where);
    }

    public function exportByMachine()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->goodsHit->exportV2($where,2);
    }

}
