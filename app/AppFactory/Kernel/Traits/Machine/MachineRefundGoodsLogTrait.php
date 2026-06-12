<?php

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineRefundGoodsLogModel;

trait MachineRefundGoodsLogTrait
{
    public function addMachineRefundGoodsLog($insert)
    {
        $data = MachineRefundGoodsLogModel::create($insert);
        return $data->mrgl_id;
    }
}
