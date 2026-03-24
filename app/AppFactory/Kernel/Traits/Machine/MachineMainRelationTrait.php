<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:39
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineMainRelationModel;

trait MachineMainRelationTrait
{
    public function getMachineMainRelationFind($where,$field = "*",$order = "")
    {
        return MachineMainRelationModel::getFind($where,$field,$order);
    }

    public function getMachineMainRelationList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineMainRelationModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMoreMachineMainRelation($insertAll)
    {
        $mmr = new MachineMainRelationModel();
        return $mmr->saveAll($insertAll);
    }

    public function getMachineMainRelationValue($where,$value)
    {
        return MachineMainRelationModel::getFieldValue($where,$value);
    }

    public function getMachineMainRelationCount($where,$value)
    {
        return MachineMainRelationModel::getCount($where,$value);
    }

    public function addMachineMainRelation($data)
    {
        return MachineMainRelationModel::create($data);
    }

    public function delMachineMainRelation($where)
    {
        $result = MachineMainRelationModel::whereDel($where);
        return $result;
    }

    public function getMachineAuxiliaryMachineColumn($where,$field = "b.*",$order = "b.m_id desc",$column = 'b.machine_id')
    {
        $data = MachineMainRelationModel::alias("a")
            ->join("machine_auxiliary b","b.m_id = a.b_mc_id")
            ->where($where)
            ->field($field)
            ->order($order)
            ->column($column);
        return $data;
    }

}