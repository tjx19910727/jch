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
        $data = MachineOnOffModel::create($insert);
        return $data->moo_id;
    }

    public function updateMachineOnOff($update,$where = [],$field = [])
    {
        return MachineOnOffModel::update($update,$where,$field);
    }

    public function delMachineOnOff($where)
    {
        $result = MachineOnOffModel::whereDel($where);
        return $result;
    }
}