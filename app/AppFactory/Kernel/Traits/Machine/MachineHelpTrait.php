<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:39
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineHelpModel;

trait MachineHelpTrait
{
    public function getMachineHelpFind($where,$field = "*",$order = "")
    {
        return MachineHelpModel::getFind($where,$field,$order);
    }

    public function getMachineHelpList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineHelpModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineHelp($insert)
    {
        !isset($this->manager['manager_id']) ?: $insert['creator'] = $this->manager['manager_id'];
        $data = MachineHelpModel::create($insert);
        return $data->mh_id;
    }

    public function updateMachineHelp($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        return MachineHelpModel::update($update,$where,$field);
    }

    public function delMachineHelp($where)
    {
        $result = MachineHelpModel::destroy($where);
        return $result;
    }
}