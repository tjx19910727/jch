<?php

namespace app\management\controller\revenue;

use app\management\controller\Common;

class RevenueRule extends Common
{
    protected $validatePath = 'app\management\validate\VRevenueRule.';

    public function saveConfig()
    {
        $postData = input();
        return $this->app->revenueRule->saveConfig($postData);
    }

    public function saveScope()
    {
        $postData = input();
        return $this->app->revenueRule->saveScope($postData);
    }

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['config_name' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenueRule->getList($where, $pageNum, "*", "rr_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->revenueRule->getFind($where, "*", "rr_id desc");
    }

}
