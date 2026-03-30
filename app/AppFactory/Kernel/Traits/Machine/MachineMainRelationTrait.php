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


}