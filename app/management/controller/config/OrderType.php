<?php

namespace app\management\controller\config;

use app\management\controller\Common;
use app\management\validate\Config\VOrderType;

class OrderType extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['order_type_name' => 'like', 'remark' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->orderType->getList($where, $pageNum);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['order_type_name' => 'like']);
        return $this->app->orderType->getFind($where);
    }

    public function add()
    {
        $postData = input();
        try { $this->validate($postData, VOrderType::class . '.add'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->orderType->addData($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData, VOrderType::class . '.update'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->orderType->updateData($postData);
    }

    public function del()
    {
        $postData = input();
        try { $this->validate($postData, VOrderType::class . '.del'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->orderType->delData($postData['ot_id']);
    }
}
