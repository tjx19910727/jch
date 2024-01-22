<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:34
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineModel;

trait MachineTrait
{
    public function getMachineValue($where,$value)
    {
        return MachineModel::getFieldValue($where,$value);
    }

    public function getMachineFind($where,$field = "*",$order = "")
    {
        return MachineModel::getFind($where,$field,$order);
    }

    public function getMachineList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachine($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MachineModel::create($insert);
        return $data->m_id;
    }

    public function updateMachine($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return MachineModel::update($update,$where,$field);
    }

    public function delMachine($where)
    {
        $result = MachineModel::destroy($where);
        return $result;
    }
}