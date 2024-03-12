<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 15:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineOnlineModel;

trait MachineOnlineTrait
{

    public function getMachineOnlineFind($where,$field = "*",$order = "")
    {
        return MachineOnlineModel::getFind($where,$field,$order);
    }

    public function getMachineOnlineList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineOnlineModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineOnline($insert)
    {
        $data = MachineOnlineModel::create($insert);
        return $data->online_id;
    }

    public function updateMachineOnline($update,$where = [],$field = [])
    {
        return MachineOnlineModel::update($update,$where,$field);
    }

    public function delMachineOnline($where)
    {
        $result = MachineOnlineModel::whereDel($where);
        return $result;
    }
}