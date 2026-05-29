<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachinePreReplenishment extends Common
{
    public function getMachineChannels()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->getMachineChannels($postData);
    }

    public function addOrder()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->addOrder($postData);
    }

    public function updateOrder()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->updateOrder($postData);
    }

    public function getOrderList()
    {
        $postData = input();
        return $this->app->machinePreReplenishment->getOrderList($postData);
    }

    public function getOrderDetail()
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
}
