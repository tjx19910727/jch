<?php

namespace app\AppFactory\Kernel\Traits\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueAccountModel;

trait RevenueAccountTrait
{
    public function getRevenueAccountFind($where, $field = "*", $order = "")
    {
        return RevenueAccountModel::getFind($where, $field, $order);
    }

    public function getRevenueAccountList($where, $pageNum = 0, $field = "*", $order = "ra_id desc")
    {
        return RevenueAccountModel::getList($where, $pageNum, $field, $order);
    }

    public function getRevenueAccountValue($where, $value, $order = "")
    {
        return RevenueAccountModel::getFieldValue($where, $value, $order);
    }

    public function addRevenueAccount($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = RevenueAccountModel::create($insert);
        return $data->ra_id;
    }

    public function updateRevenueAccount($update, $where = [], $field = [])
    {
        return RevenueAccountModel::update($update, $where, $field);
    }

    public function delRevenueAccount($where)
    {
        return RevenueAccountModel::destroy($where);
    }
}
