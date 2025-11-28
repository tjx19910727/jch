<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/8
 * Time: 16:31
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineOnOff;

class MachineOnOff extends Common
{

    protected $field = "moo_id,m_id,
    (CASE  
    WHEN (machine_id is null or machine_id = '') THEN (select machine_id from machine m1 where `m1`.`m_id` = a.m_id limit 1) 
    ELSE machine_id
     END) 
    machine_id,
        (SELECT machine_name FROM machine WHERE `machine`.`m_id` = `a`.`m_id` GROUP BY machine.m_id LIMIT 1) machine_name,
    on_off_ckc,on_off_machine,status,create_time,update_time";
    protected $validatePath = VMachineOnOff::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["machine_id" => "like","machine_name" => "like"]);
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'machine_id');
        if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
        return $this->app->machineOnOff->getList($where,$pageNum,$this->field,'update_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineOnOff->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineOnOff->addOf($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineOnOff->updateOf($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineOnOff->delOf($postData);
    }

    /**
     * 导入营业配置Excel表
     * @return array|string
     */
    public function importCkc()
    {
        $postData = input();
        return $this->app->machineOnOff->importCkc($postData);
    }

    /**
     * 导入开关机配置Excel表
     * @return array|string
     */
    public function importOnOff()
    {
        $postData = input();
        return $this->app->machineOnOff->importOnOff($postData);
    }

    /**
     * 导出营业配置表
     * @return array|\think\response\Json
     */
    public function exportOnOff()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->machineOnOff->exportOnOff($where);
    }
}