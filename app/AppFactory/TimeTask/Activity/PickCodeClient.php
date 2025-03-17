<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/2/6
 * Time: 13:54
 */

namespace app\AppFactory\TimeTask\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class PickCodeClient extends TimeTaskBase
{
    use ActivityPickCodeTrait;
    use SaleOrdersTrait;

    // 定时检查待使用中的取货码，超时修改为待使用
    public function updateInUseForUnused()
    {
        $where['status'] = 5;
        $codeList = $this->getActivityPickCodeList($where,0,'*',"order_id asc");
        if ($codeList) {
            $codeList = $codeList->toArray();
            foreach ($codeList as $item) {
                $update = [];
                if (!$item['order_id'])
                    $update['status'] = 1;
                else {
                    $order = $this->getSaleOrdersFind(['order_id' => $item['order_id']]);
                    if ($order && $order['create_time'] < time() - (env("PickCode.time_out") ?? 120)) {
                        $update['status'] = 1;
                    }
                }
                if ($update) {
                    $update['apc_id'] = $item['apc_id'];
                    $this->updateActivityPickCode($update);
                    actionLog($this->getLS(),'重置取货码状态为待使用');
                }
            }
        }
    }
}