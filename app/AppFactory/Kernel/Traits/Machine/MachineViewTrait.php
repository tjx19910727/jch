<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:02
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineViewModel;

trait MachineViewTrait
{
    public function getMachineViewValue($where,$value,$order = "")
    {
        return MachineViewModel::getFieldValue($where,$value,$order);
    }

    public function getMachineViewFind($where,$field = "*",$order = "")
    {
        return MachineViewModel::getFind($where,$field,$order);
    }

    public function getMachineViewList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '')
    {
        return MachineViewModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function addMachineView($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MachineViewModel::create($insert);
        return $data->id;
    }

    public function updateMachineView($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return MachineViewModel::update($update,$where,$field);
    }

    public function delMachineView($where)
    {
        $result = MachineViewModel::whereDel($where);
        return $result;
    }
}