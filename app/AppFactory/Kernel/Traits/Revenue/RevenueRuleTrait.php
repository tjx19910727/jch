<?php

namespace app\AppFactory\Kernel\Traits\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueRuleItemModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleItemTierModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleMachineModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleModel;

trait RevenueRuleTrait
{
    public function getRevenueRuleFind($where, $field = "*", $order = "")
    {
        return RevenueRuleModel::getFind($where, $field, $order);
    }

    public function getRevenueRuleList($where, $pageNum = 0, $field = "*", $order = "rr_id desc")
    {
        return RevenueRuleModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueRule($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = RevenueRuleModel::create($insert);
        return $data->rr_id;
    }

    public function updateRevenueRule($update, $where = [], $field = [])
    {
        return RevenueRuleModel::update($update, $where, $field);
    }

    public function delRevenueRule($where)
    {
        return RevenueRuleModel::destroy($where);
    }

    public function getRevenueRuleItemFind($where, $field = "*", $order = "")
    {
        return RevenueRuleItemModel::getFind($where, $field, $order);
    }

    public function getRevenueRuleItemList($where, $pageNum = 0, $field = "*", $order = "sort asc,rri_id asc")
    {
        return RevenueRuleItemModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueRuleItem($insert)
    {
        $data = RevenueRuleItemModel::create($insert);
        return $data->rri_id;
    }

    public function updateRevenueRuleItem($update, $where = [], $field = [])
    {
        return RevenueRuleItemModel::update($update, $where, $field);
    }

    public function delRevenueRuleItem($where)
    {
        return RevenueRuleItemModel::destroy($where);
    }

    public function getRevenueRuleItemTierFind($where, $field = "*", $order = "")
    {
        return RevenueRuleItemTierModel::getFind($where, $field, $order);
    }

    public function getRevenueRuleItemTierList($where, $pageNum = 0, $field = "*", $order = "threshold_min asc,rrit_id asc")
    {
        return RevenueRuleItemTierModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueRuleItemTier($insert)
    {
        $data = RevenueRuleItemTierModel::create($insert);
        return $data->rrit_id;
    }

    public function updateRevenueRuleItemTier($update, $where = [], $field = [])
    {
        return RevenueRuleItemTierModel::update($update, $where, $field);
    }

    public function delRevenueRuleItemTier($where)
    {
        return RevenueRuleItemTierModel::destroy($where);
    }

    public function getRevenueRuleMachineFind($where, $field = "*", $order = "")
    {
        return RevenueRuleMachineModel::getFind($where, $field, $order);
    }

    public function getRevenueRuleMachineList($where, $pageNum = 0, $field = "*", $order = "rrm_id desc")
    {
        return RevenueRuleMachineModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueRuleMachine($insert)
    {
        $data = RevenueRuleMachineModel::create($insert);
        return $data->rrm_id;
    }

    public function updateRevenueRuleMachine($update, $where = [], $field = [])
    {
        return RevenueRuleMachineModel::update($update, $where, $field);
    }

    public function delRevenueRuleMachine($where)
    {
        return RevenueRuleMachineModel::destroy($where);
    }
}
