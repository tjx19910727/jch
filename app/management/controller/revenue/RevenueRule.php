<?php

namespace app\management\controller\revenue;

use app\management\controller\Common;

class RevenueRule extends Common
{
    protected $validatePath = 'app\management\validate\VRevenueRule.';

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['rule_name' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueRule->getList($where, $pageNum, "*", "rr_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->revenueRule->getFind($where, "*", "rr_id desc");
    }

    public function add()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'add'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueRule->add($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'update'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueRule->update($postData);
    }

    public function addItem()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'addItem'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueRule->addItem($postData);
    }

    public function updateItem()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'updateItem'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueRule->updateItem($postData);
    }

    public function getItemList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueRule->getItemList($where, $pageNum);
    }

    public function delItem()
    {
        $rriId = input('rri_id');
        if (!$rriId) return returnState(100, '分账策略明细ID不能为空');
        return $this->app->revenueRule->delItem($rriId);
    }

    public function addTier()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'addTier'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueRule->addTier($postData);
    }

    public function updateTier()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'updateTier'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenueRule->updateTier($postData);
    }

    public function getTierList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueRule->getTierList($where, $pageNum);
    }

    public function delTier()
    {
        $rritId = input('rrit_id');
        if (!$rritId) return returnState(100, '阶梯分账明细ID不能为空');
        return $this->app->revenueRule->delTier($rritId);
    }

    public function bindMachine()
    {
        $postData = input();
        return $this->app->revenueRule->bindMachine($postData);
    }

    public function getMachineList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueRule->getMachineList($where, $pageNum);
    }

    public function unbindMachine()
    {
        $rrmId = input('rrm_id');
        if (!$rrmId) return returnState(100, '设备分账策略绑定ID不能为空');
        return $this->app->revenueRule->unbindMachine($rrmId);
    }
}
