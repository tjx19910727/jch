<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;

trait MachineChannelTrait
{

    public function getMachineChannelFind($where,$field = "*",$order = "")
    {
        return MachineChannelModel::getFind($where,$field,$order);
    }

    public function getMachineChannelList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineChannelModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineChannel($insert)
    {
        $data = MachineChannelModel::create($insert);
        return $data->mc_id;
    }

    public function updateMachineChannel($update,$where = [],$field = [])
    {
        return MachineChannelModel::update($update,$where,$field);
    }

    public function delMachineChannel($where)
    {
        $result = MachineChannelModel::destroy($where);
        return $result;
    }
}