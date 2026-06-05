<?php

namespace app\management\controller\revenue;

use app\management\controller\Common;

class RevenueOrder extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, [
            'order_id' => 'like',
            'trade_no' => 'like',
            'sp_id' => 'in',
            'rule_mode' => 'in',
            'source' => 'in',
            'status' => 'in',
            'payer_ao_id' => 'in',
            'receiver_ao_id' => 'in',
            'manager_id' => 'in',
            'ra_id' => 'in',
            'machine_id' => 'like',
            'machine_name' => 'like',
            'period_key' => 'like',
            'create_time' => 'between',
            'revenue_time' => 'between',
        ]);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueOrder->getList($where, $pageNum, "*", "ro_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->revenueOrder->getFind($where, "*", "ro_id desc");
    }

    public function getDetail()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->revenueOrder->getDetail($where);
    }

    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, [
            'order_id' => 'like',
            'trade_no' => 'like',
            'sp_id' => 'in',
            'rule_mode' => 'in',
            'source' => 'in',
            'status' => 'in',
            'payer_ao_id' => 'in',
            'receiver_ao_id' => 'in',
            'manager_id' => 'in',
            'ra_id' => 'in',
            'machine_id' => 'like',
            'machine_name' => 'like',
            'period_key' => 'like',
            'create_time' => 'between',
            'revenue_time' => 'between',
        ]);
        return $this->app->revenueOrder->export($where);
    }

    public function mockPaySuccess()
    {
        $postData = input();
        return $this->app->revenueOrder->mockPaySuccess($postData);
    }
}
