<?php

namespace app\AppFactory\TimeTask\Revenue;

use app\AppFactory\Kernel\Service\Revenue\RevenueSettlementService;
use app\AppFactory\TimeTask\TimeTaskBase;

class RevenueClient extends TimeTaskBase
{
    public function settleDue()
    {
        $result = (new RevenueSettlementService())->settleDue();
        return "处理完成：总数{$result['total']}，成功{$result['success']}，失败{$result['failed']}";
    }
}
