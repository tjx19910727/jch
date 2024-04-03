<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/28
 * Time: 15:09
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineGroupMgModel;

trait MachineGroupMgTrait
{
    public function getMachineGroupMgFind($where,$field = "*",$order = "")
    {
        return MachineGroupMgModel::getFind($where,$field,$order);
    }

    public function getMachineGroupMgColumn($where,$column)
    {
        return MachineGroupMgModel::getColumn($where,$column);
    }

    public function getMachineGroupMgList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineGroupMgModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineGroupMg($insert)
    {
        $data = MachineGroupMgModel::create($insert);
        return $data->id;
    }

    public function addMachineGroupMgMore($insertAll)
    {
        $mg = new MachineGroupMgModel();
        return $mg->saveAll($insertAll);
    }

    public function updateMachineGroupMg($update,$where = [],$field = [])
    {
        return MachineGroupMgModel::update($update,$where,$field);
    }

    public function delMachineGroupMg($where)
    {
        $result = MachineGroupMgModel::whereDel($where);
        return $result;
    }
}