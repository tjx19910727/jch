<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 8:54
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineOnline extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["machine_id" => "like"]);
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->machineOnline->getList($where,$pageNum,$this->field,'online_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineOnline->getFind($where);
    }

    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ["machine_id" => "like"]);
        if (!isset($where['machine_id'])) {
            $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'machine_id');
            if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
        }
        return $this->app->machineOnline->export($where);

    }

    /**
     * Export today's operating-machine online snapshots.
     */
    public function exportTodayOperatingSnapshot()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ["machine_id" => "like"]);
        if (!isset($where['machine_id'])) {
            $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'machine_id');
            if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
        }
        return $this->app->machineOnline->exportTodayOperatingSnapshot($where);
    }

}
