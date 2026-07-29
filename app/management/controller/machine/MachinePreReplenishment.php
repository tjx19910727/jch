<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachinePreReplenishment extends Common
{
    public function getList()
    {
        $postData = [];
        $where = [];
        try {
            $postData = input();
            $where = $this->getWhere([]);
            return $this->app->machinePreReplenishment->getOrderList($postData, $where);
        } catch (\Throwable $e) {
            actionLog([
                'request_data' => $postData,
                'where' => $where,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], '预补货列表查询异常', 'getList_error');
            return returnState(5000, '系统错误');
        }
    }

    public function getMachineChannels()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->getMachineChannels($postData);
    }

    public function add()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->addOrder($postData);
    }

    public function update()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->updateOrder($postData);
    }

    public function getDetail()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->getOrderDetail($postData);
    }

    public function exportOrder()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->exportOrder($postData);
    }

    public function reportLog()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->reportLog($postData);
    }

    /**
     * 手动完结预补货单
     */
    public function finish()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->finishOrder($postData);
    }

    /**
     * 下发获取补货视频
     * @return array|string
     */
    public function getReplenishmentVideo()
    {
        $order_id = input('order_id');
        $machine_id = input('machine_id');
        $status = input('status', 0);
        $order = $this->app->machinePreReplenishment->getOrderInfo(['id' => $order_id], 'record_no');
        if (!$order) return returnState(100, '未找到补货记录');
        $where = ['order_id' => $order_id, 'machine_id' => $machine_id];
        $video = $this->app->machinePreReplenishment->getReplenishmentDetailVideo($where);

        if (!$video || empty($video['replenishment_video']) || $status == 1) {
            //加上频率限制，避免重复下发，300s内同一订单同一设备只能下发一次
            $cacheKey = "replenishment_video_{$order_id}_{$machine_id}";
            if (cache($cacheKey)) {
                return returnState(200, '5分钟内，同一订单同一设备只能下发一次');
            }
            cache($cacheKey, true, 300);
            $otherData = ['record_no' => $order['record_no']];
            $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], 'replenishmentVideo', $otherData);
            return is_object($result) ? returnState(200, '正在从机器端获取补货视频，请稍做等待后下载', $result) :
                $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        }
        return returnState(200, '查询成功', $video);
    }

    /**
     * 重置补货次数
     */
    public function resetReplenishmentCount()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->resetReplenishmentCount($postData);
    }

    /**
     * 删除补货单
     */
    public function delete()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->deleteOrder($postData);
    }
}
