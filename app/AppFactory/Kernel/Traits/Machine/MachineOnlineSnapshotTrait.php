<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/19
 * Time: 11:21
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineOnlineSnapshotModel;

trait MachineOnlineSnapshotTrait
{
    public function getMachineOnlineSnapshotFind($where, $field = "*", $order = "")
    {
        return MachineOnlineSnapshotModel::getFind($where, $field, $order);
    }

    public function getMachineOnlineSnapshotList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return MachineOnlineSnapshotModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addMachineOnlineSnapshot($insert)
    {
        $data = MachineOnlineSnapshotModel::create($insert);
        return $data->mos_id;
    }

    public function updateMachineOnlineSnapshot($update, $where = [], $field = [])
    {
        return MachineOnlineSnapshotModel::update($update, $where, $field);
    }

    public function delMachineOnlineSnapshot($where)
    {
        return MachineOnlineSnapshotModel::whereDel($where);
    }
}

