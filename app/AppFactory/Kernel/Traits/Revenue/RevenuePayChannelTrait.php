<?php

namespace app\AppFactory\Kernel\Traits\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenuePayChannelModel;

trait RevenuePayChannelTrait
{
    public function getRevenuePayChannelFind($where, $field = "*", $order = "")
    {
        return RevenuePayChannelModel::getFind($where, $field, $order);
    }

    public function getRevenuePayChannelList($where, $pageNum = 0, $field = "*", $order = "rpc_id desc")
    {
        return RevenuePayChannelModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenuePayChannel($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = RevenuePayChannelModel::create($insert);
        return $data->rpc_id;
    }

    public function updateRevenuePayChannel($update, $where = [], $field = [])
    {
        return RevenuePayChannelModel::update($update, $where, $field);
    }

    public function delRevenuePayChannel($where)
    {
        return RevenuePayChannelModel::destroy($where);
    }
}
