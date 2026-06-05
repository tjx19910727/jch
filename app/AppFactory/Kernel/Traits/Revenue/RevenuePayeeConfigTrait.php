<?php

namespace app\AppFactory\Kernel\Traits\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenuePayeeConfigModel;

trait RevenuePayeeConfigTrait
{
    public function getRevenuePayeeConfigFind($where, $field = "*", $order = "")
    {
        return RevenuePayeeConfigModel::getFind($where, $field, $order);
    }

    public function getRevenuePayeeConfigList($where, $pageNum = 0, $field = "*", $order = "rpcfg_id desc")
    {
        return RevenuePayeeConfigModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenuePayeeConfig($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = RevenuePayeeConfigModel::create($insert);
        return $data->rpcfg_id;
    }

    public function updateRevenuePayeeConfig($update, $where = [], $field = [])
    {
        return RevenuePayeeConfigModel::update($update, $where, $field);
    }

    public function delRevenuePayeeConfig($where)
    {
        return RevenuePayeeConfigModel::destroy($where);
    }
}
