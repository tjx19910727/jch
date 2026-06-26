<?php

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineRefundGoodsLogModel;

trait MachineRefundGoodsLogTrait
{
    public function getMachineRefundGoodsLogFind($where, $field = "*", $order = "")
    {
        return MachineRefundGoodsLogModel::getFind($where, $field, $order);
    }

    public function getMachineRefundGoodsLogList($where, $pageNum = null, $field = "*", $order = "", $eachFun = "")
    {
        return MachineRefundGoodsLogModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineRefundGoodsLog($insert)
    {
        $data = MachineRefundGoodsLogModel::create($insert);
        return $data->mrgl_id;
    }

    public function updateMachineRefundGoodsLog($update, $where = [], $field = [])
    {
        return MachineRefundGoodsLogModel::update($update, $where, $field);
    }

    public function delMachineRefundGoodsLog($where)
    {
        return MachineRefundGoodsLogModel::whereDel($where);
    }
}
