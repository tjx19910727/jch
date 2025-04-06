<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:10
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineVersionModel;

trait MachineVersionTrait
{
    public function getMachineVersionFind($where,$field = "*",$order = "")
    {
        return MachineVersionModel::getFind($where,$field,$order);
    }

    public function getMachineVersionList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineVersionModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineVersion($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MachineVersionModel::create($insert);
        return $data->mv_id;
    }

    public function updateMachineVersion($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return MachineVersionModel::update($update,$where,$field);
    }

    public function delMachineVersion($where)
    {
        $result = MachineVersionModel::whereDel($where);
        return $result;
    }
}