<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachineServiceFee extends Common
{
    public function setFeePreview()
    {
        return $this->app->machineServiceFee->setFeePreview(input());
    }

    public function setFee()
    {
        return $this->app->machineServiceFee->setFee(input());
    }

    public function renewPreview()
    {
        return $this->app->machineServiceFee->renewPreview(input());
    }

    public function createRenewOrder()
    {
        return $this->app->machineServiceFee->createRenewOrder(input());
    }

    public function getPayMethods()
    {
        return $this->app->machineServiceFee->getPayMethods();
    }

    public function getRenewNoticeStatus()
    {
        return $this->app->machineServiceFee->getRenewNoticeStatus();
    }

    public function createPayQr()
    {
        return $this->app->machineServiceFee->createPayQr(input());
    }

    public function getPayStatus()
    {
        return $this->app->machineServiceFee->getPayStatus(input());
    }

    public function getSuccessOrderList()
    {
        return $this->app->machineServiceFee->getSuccessOrderList(input());
    }

    public function getSuccessOrderFind()
    {
        return $this->app->machineServiceFee->getSuccessOrderFind(input());
    }

    public function refundOrder()
    {
        return $this->app->machineServiceFee->refundOrder(input());
    }

}
