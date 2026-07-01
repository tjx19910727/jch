<?php

namespace app\AppFactory\Kernel\Traits\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueOrderModel;

trait RevenueOrderTrait
{
    public function getRevenueOrderFind($where, $field = "*", $order = "")
    {
        return RevenueOrderModel::getFind($where, $field, $order);
    }

    public function getRevenueOrderList($where, $pageNum = 0, $field = "*", $order = "ro_id desc")
    {
        return RevenueOrderModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueOrder($insert)
    {
        $data = RevenueOrderModel::create($insert);
        return $data->ro_id;
    }

    public function updateRevenueOrder($update, $where = [], $field = [])
    {
        return RevenueOrderModel::update($update, $where, $field);
    }
}
