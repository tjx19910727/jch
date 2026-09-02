<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

use app\AppFactory\Kernel\Service\FaultNotice\FaultReportService;
use think\facade\Db;

/**
 * 新故障流程统一入口；与旧MachineErrorCodeTrait并存。
 */
trait FaultReportTrait
{
    protected function shouldUseLegacyFaultCodeFlow()
    {
        return !Db::name('machine_fault_receiver')->value('receiver_id');
    }

    public function reportFaultCode($machine = null, $message = null)
    {
        $machine = $machine === null ? ($this->machine ?? []) : $machine;
        $message = $message === null ? ($this->message ?? []) : $message;
        return (new FaultReportService())->report($machine, $message);
    }
}
