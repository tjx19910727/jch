<?php

namespace app\AppFactory\Kernel\Traits\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigScopeModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleModel;

trait RevenueRuleTrait
{
    public function getRevenueRuleConfigFind($where, $field = "*", $order = "")
    {
        return RevenueRuleConfigModel::getFind($where, $field, $order);
    }

    public function getRevenueRuleConfigList($where, $pageNum = 0, $field = "*", $order = "rrcfg_id desc")
    {
        return RevenueRuleConfigModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueRuleConfig($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = RevenueRuleConfigModel::create($insert);
        return $data->rrcfg_id;
    }

    public function updateRevenueRuleConfig($update, $where = [], $field = [])
    {
        return RevenueRuleConfigModel::update($update, $where, $field);
    }

    public function delRevenueRuleConfig($where)
    {
        return RevenueRuleConfigModel::destroy($where);
    }

    public function getRevenueRuleConfigScopeFind($where, $field = "*", $order = "")
    {
        return RevenueRuleConfigScopeModel::getFind($where, $field, $order);
    }

    public function getRevenueRuleConfigScopeList($where, $pageNum = 0, $field = "*", $order = "sort asc,rrcs_id asc")
    {
        return RevenueRuleConfigScopeModel::getList($where, $pageNum, $field, $order);
    }

    public function addRevenueRuleConfigScope($insert)
    {
        $data = RevenueRuleConfigScopeModel::create($insert);
        return $data->rrcs_id;
    }

    public function updateRevenueRuleConfigScope($update, $where = [], $field = [])
    {
        return RevenueRuleConfigScopeModel::update($update, $where, $field);
    }

    public function delRevenueRuleConfigScope($where)
    {
        return RevenueRuleConfigScopeModel::destroy($where);
    }

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
}
