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
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->machineChannelStockReport->getSkuStockList($where, $pageNum, $isOperating);
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
