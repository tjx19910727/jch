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
//        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
//        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->machineCheckStock->getList($where,$pageNum,'
            id,m_id,machine_id,machine_name,channel_code,g_name,sku,gc_name,system_stock,check_stock,creator,status,create_date,create_time
        ','id desc');
    }

    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like",'sku' => "like"]);
//        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
//        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->machineCheckStock->exportExcel($where);
    }

}
