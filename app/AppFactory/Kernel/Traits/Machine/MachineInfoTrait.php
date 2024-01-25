<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineInfoModel;

trait MachineInfoTrait
{
    public function getMachineInfoFind($where,$field = "*",$order = "")
    {
        return MachineInfoModel::getFind($where,$field,$order);
    }

    public function getMachineInfoList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineInfoModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineInfo($insert)
    {
        $data = MachineInfoModel::create($insert);
        return $data->mi_id;
    }

    public function updateMachineInfo($update,$where = [],$field = [])
    {
        return MachineInfoModel::update($update,$where,$field);
    }

    public function delMachineInfo($where)
    {
        $result = MachineInfoModel::whereDel($where);
        return $result;
    }
}