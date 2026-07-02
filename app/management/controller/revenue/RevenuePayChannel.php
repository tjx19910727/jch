<?php

namespace app\management\controller\revenue;

use app\management\controller\Common;

class RevenuePayChannel extends Common
{
    protected $validatePath = 'app\management\validate\VRevenuePayChannel.';

    public function add()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'add'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenuePayChannel->addData($postData);
    }

    public function update()
    {
        $postData = input();
        try { $this->validate($postData, $this->validatePath . 'update'); } catch (\Exception $e) { return returnValidate($e->getMessage()); }
        return $this->app->revenuePayChannel->updateData($postData);
    }

    public function getList()
    {
        $postData = input();
        unset($postData['payee_type']);
        $where = $this->getWhere($postData, false, ['channel_name' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->revenuePayChannel->getList($where, $pageNum, "*", "rpc_id desc");
    }

    public function getFind()
    {
        $postData = input();
        unset($postData['payee_type']);
        $where = $this->getWhere($postData);
        return $this->app->revenuePayChannel->getFind($where, "*", "rpc_id desc");
    }

    public function del()
    {
        $rpcId = input('rpc_id');
        if (!$rpcId) return returnState(100, '分账渠道配置ID不能为空');
        return $this->app->revenuePayChannel->delData($rpcId);
    }
}
