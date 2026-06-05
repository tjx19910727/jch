<?php

namespace app\management\controller\revenue;

use app\management\controller\Common;

class RevenueAccount extends Common
{
    protected $validatePath = 'app\management\validate\VRevenueAccount.';

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['account_name' => 'like', 'account' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueAccount->getList($where, $pageNum, "*", "ra_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->revenueAccount->getFind($where, "*", "ra_id desc");
    }

    public function add()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'add'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueAccount->add($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'update'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueAccount->update($postData);
    }

    public function del()
    {
        $raId = input('ra_id');
        if (!$raId) return returnState(100, '分账账户ID不能为空');
        return $this->app->revenueAccount->del($raId);
    }
}
