<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 20:05
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineGroupModel;

trait MachineGroupTrait
{
    public function getMachineGroupFind($where,$field = "*",$order = "")
    {
        return MachineGroupModel::getFind($where,$field,$order);
    }

    public function getMachineGroupList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineGroupModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineGroup($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MachineGroupModel::create($insert);
        return $data->mg_id;
    }

    public function updateMachineGroup($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return MachineGroupModel::update($update,$where,$field);
    }

    public function delMachineGroup($where)
    {
        $result = MachineGroupModel::whereDel($where);
        return $result;
    }
}