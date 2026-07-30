<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 11:44
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineLayoutModelModel;

trait MachineLayoutModelTrait
{
    public function getMachineLayoutModelFind($where, $field = "*", $order = "")
    {
        return MachineLayoutModelModel::getFind($where, $field, $order);
    }

    public function getMachineLayoutModelList($where, $pageNum = 0, $field = "*", $order = "mlm_id desc", $eachFn = "")
    {
        return MachineLayoutModelModel::getList($where, $pageNum, $field, $order, $eachFn);
    }

    public function addMachineLayoutModel($insert)
    {
        $data = MachineLayoutModelModel::create($insert);
        return $data->mlm_id;
    }

    public function updateMachineLayoutModel($update, $where = [], $field = [])
    {
        return MachineLayoutModelModel::update($update, $where, $field);
    }

    public function delMachineLayoutModel($where)
    {
        return MachineLayoutModelModel::whereDel($where);
    }

    public function getMachineLayoutModelCount($where)
    {
        return MachineLayoutModelModel::getCount($where);
    }
}