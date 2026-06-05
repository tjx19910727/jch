<?php

namespace app\management\controller\revenue;

use app\management\controller\Common;

class RevenuePayeeConfig extends Common
{
    public function save()
    {
        $postData = input();
        return $this->app->revenuePayeeConfig->saveByPayee($postData);
    }

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenuePayeeConfig->getList($where, $pageNum);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->revenuePayeeConfig->getFind($where);
    }

    public function checkConfig()
    {
        return $this->app->revenuePayeeConfig->checkConfig();
    }
}
