<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 11:44
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineLayoutDetailModel;

trait MachineLayoutDetailTrait
{
    public function getMachineLayoutDetailFind($where, $field = "*", $order = "")
    {
        return MachineLayoutDetailModel::getFind($where, $field, $order);
    }

    public function getMachineLayoutDetailList($where, $pageNum = 0, $field = "*", $order = "row_index asc,col_index asc", $eachFn = "")
    {
        return MachineLayoutDetailModel::getList($where, $pageNum, $field, $order, $eachFn);
    }

    public function addMachineLayoutDetail($insert)
    {
        $data = MachineLayoutDetailModel::create($insert);
        return $data->mld_id;
    }

    public function addMachineLayoutDetailAll($insertAll)
    {
        return MachineLayoutDetailModel::insertAll($insertAll);
    }

    public function updateMachineLayoutDetail($update, $where = [], $field = [])
    {
        return MachineLayoutDetailModel::update($update, $where, $field);
    }

    public function delMachineLayoutDetail($where)
    {
        return MachineLayoutDetailModel::whereDel($where);
    }

    public function getMachineLayoutDetailCount($where)
    {
        return MachineLayoutDetailModel::getCount($where);
    }

    public function getMachineLayoutDetailColumn($where, $column)
    {
        return MachineLayoutDetailModel::getColumn($where, $column);
    }
}