<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachinePreReplenishment extends Common
{
    public function getList()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->getOrderList($postData);
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
     * 下发获取补货视频
     * @return array|string
     */
    public function getReplenishmentVideo()
    {
        $order_id = input('order_id');
        $machine_id = input('machine_id');
        $status = input('status', 0);
        $where = ['order_id' => $order_id, 'machine_id' => $machine_id];
        $detail = $this->app->machinePreReplenishment->getReplenishmentDetailVideo($where);
        if (!$detail) return returnState(100, '未找到补货记录');
        if (empty($detail['replenishment_video']) || $status == 1) {
            $otherData = ['record_no' => $detail['record_no']];
            $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], 'replenishmentVideo', $otherData);
            return is_object($result) ? returnState(200, '正在从机器端获取补货视频，请稍做等待后下载', $result) :
                $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        }
        return returnState(200, '查询成功', $detail);
    }
}
