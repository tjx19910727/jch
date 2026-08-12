<?php

namespace app\management\controller\config;

use app\management\controller\Common;
use app\management\validate\Config\VPayType;

class PayType extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['pay_type_name' => 'like', 'remark' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->payType->getList($where, $pageNum);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['pay_type_name' => 'like']);
        return $this->app->payType->getFind($where);
    }

    public function getTree()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['pay_type_name' => 'like', 'remark' => 'like']);
        if (!isset($postData['status'])) {
            $where['status'] = 1;
        }
        return $this->app->payType->getTree($where);
    }

    public function add()
    {
        $postData = input();
        try { $this->validate($postData, VPayType::class . '.add'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->payType->addData($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData, VPayType::class . '.update'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->payType->updateData($postData);
    }

    public function del()
    {
        $postData = input();
        try { $this->validate($postData, VPayType::class . '.del'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->payType->delData($postData['pt_id']);
    }
}
