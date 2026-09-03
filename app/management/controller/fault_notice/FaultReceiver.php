<?php

namespace app\management\controller\fault_notice;

use app\management\controller\Common;

/**
 * 故障通知接收人后台接口。
 */
class FaultReceiver extends Common
{
    public function getList()
    {
        $postData = input();
        $pageNum = intval($postData['pageNum'] ?? 20);
        $pageNum = $pageNum > 0 ? min($pageNum, 100) : 20;
        return $this->app->faultNotice->getReceiverList($postData, $pageNum);
    }

    public function getDetail()
    {
        $postData = input();
        $receiverId = intval($postData['receiver_id'] ?? 0);
        if ($receiverId <= 0) {
            return returnValidate('接收人ID不能为空');
        }
        return $this->app->faultNotice->getReceiverDetail($receiverId);
    }

    public function add()
    {
        return $this->app->faultNotice->addReceiver(input());
    }

    public function update()
    {
        return $this->app->faultNotice->updateReceiver(input());
    }

    public function updateStatus()
    {
        $postData = input();
        return $this->app->faultNotice->updateReceiverStatus(
            intval($postData['receiver_id'] ?? 0),
            intval($postData['status'] ?? 0)
        );
    }

    public function delete()
    {
        $postData = input();
        return $this->app->faultNotice->deleteReceiver(
            intval($postData['receiver_id'] ?? 0)
        );
    }
}
