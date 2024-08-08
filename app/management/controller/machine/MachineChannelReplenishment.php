<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 20:02
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineChannelReplenishment extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","machine_name" => "like","sku" => "like","channel_code" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->machineChannelReplenishment->getList($where,$pageNum,'*','id desc');
    }

    /**
     * 导出补货报表
     * @return array|\think\response\Json
     */
    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","machine_name" => "like","sku" => "like","channel_code" => "like"]);
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->machineChannelReplenishment->export($where);
    }

}