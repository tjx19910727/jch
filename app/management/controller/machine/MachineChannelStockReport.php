<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 14:11
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineChannelStockReport extends Common
{
    public function getList()
    {
        $postData = input();
        $isOperating = $postData['is_operating'] ?? null;
        unset($postData['is_operating']);
        $where = $this->getWhere($postData,false,['machine_id' => "like","sku" => "like","g_name" => "like"]);
        $field = "sku,g_name,bar_code,model,g_id,
        sum(mc_stock) mc_stock,
        sum(pre_stock) pre_stock,
        sum(standby_stock) standby_stock,
        sum(bad_stock) bad_stock,
        sum(total_stock + standby_stock) total_stock,
        retail_price
        ";
        $order = "total_stock desc";
        $pageNum = $postData['pageNum'] ?? 0;
        $group = "g_id";
        return $this->app->machineChannelStockReport->getMcsList($where,$pageNum,$field,$order,$group,$isOperating);
    }

    public function exportBySku()
    {
        $postData = input();
        $isOperating = $postData['is_operating'] ?? null;
        unset($postData['is_operating']);
        $where = $this->getWhere($postData,false,['machine_id' => "like","sku" => "like"]);
        return $this->app->machineChannelStockReport->export($where,1,$isOperating);
    }

    public function exportByMachine()
    {
        $postData = input();
        $isOperating = $postData['is_operating'] ?? null;
        unset($postData['is_operating']);
        $where = $this->getWhere($postData,false,['machine_id' => "like","sku" => "like"], 'mcs.');
        return $this->app->machineChannelStockReport->export($where,2,$isOperating);
    }
}
