<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 18:00
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineCheckStock extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like",'sku' => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->machineCheckStock->getList($where,$pageNum,'
            machine_id,machine_name,channel_code,g_name,sku,gc_name,check_stock,stock_reserve,creator,create_date,create_time
        ','create_date desc');
    }

    public function exportBySku()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like",'sku' => "like"]);
        return $this->app->machineCheckStock->exportSku($where);
    }

    public function exportByMachine()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like",'sku' => "like"]);
        return $this->app->machineCheckStock->exportMachine($where);
    }

}