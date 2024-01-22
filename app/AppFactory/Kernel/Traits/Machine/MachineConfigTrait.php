<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:36
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineConfigModel;

trait MachineConfigTrait
{


    public function getMachineConfigFind($where, $field = "*", $order = "")
    {
        return MachineConfigModel::getFind($where, $field, $order);
    }

    public function getMachineConfigList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineConfigModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineConfig($insert)
    {
        !isset($this->manager['manager_id']) ?: $insert['creator'] = $this->manager['manager_id'];
        $data = MachineConfigModel::create($insert);
        return $data->mc_id;
    }

    public function updateMachineConfig($update, $where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        return MachineConfigModel::update($update, $where, $field);
    }

    public function delMachineConfig($where)
    {
        $result = MachineConfigModel::destroy($where);
        return $result;
    }
}