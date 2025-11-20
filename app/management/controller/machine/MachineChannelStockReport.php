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
        $where = $this->getWhere($postData,false,['machine_id' => "like","sku" => "like","g_name" => "like"]);
        $field = "sku,g_name,bar_code,model,
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
        return $this->app->machineChannelStockReport->getMcsList($where,$pageNum,$field,$order,$group);
    }

    public function exportBySku()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","sku" => "like"]);
        return $this->app->machineChannelStockReport->export($where);
    }

    public function exportByMachine()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","sku" => "like"]);
        return $this->app->machineChannelStockReport->export($where,2);
    }
}