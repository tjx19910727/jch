<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/8
 * Time: 9:48
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineOnOffModel;

trait MachineOnOffTrait
{

    public function getMachineOnOffValue($where,$field)
    {
        return MachineOnOffModel::getFieldValue($where,$field);
    }

    public function getMachineOnOffFind($where,$field = "*",$order = "")
    {
        return MachineOnOffModel::getFind($where,$field,$order);
    }

    public function getMachineOnOffList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineOnOffModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineOnOff($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $data = MachineOnOffModel::create($insert);
        return $data->moo_id;
    }

    public function updateMachineOnOff($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return MachineOnOffModel::update($update,$where,$field);
    }

    public function delMachineOnOff($where)
    {
        $result = MachineOnOffModel::whereDel($where);
        return $result;
    }
}