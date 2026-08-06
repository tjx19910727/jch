<?php

namespace app\AppFactory\TimeTask\WeiCheng;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Service\WeiCheng\WcOrderSyncRetryService;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class WeiChengClient extends TimeTaskBase
{
    use WcBaseTrait, MachineTrait;

    public function retryOrderSync()
    {
        $service = new WcOrderSyncRetryService();
        $result = ['total' => 0, 'success' => 0, 'failed' => 0, 'final' => 0];
        foreach ($service->claimDueTasks(50) as $task) {
            $result['total']++;
            try {
                $order = $this->getSaleOrdersFind(['order_id' => intval($task['order_id'])]);
                $detail = $this->getSaleOrdersDetailsFind(['sod_id' => intval($task['sod_id'])]);
                if (!$order || !$detail) {
                    throw new \Exception('订单或订单明细不存在');
                }
                $order = is_object($order) && method_exists($order, 'toArray') ? $order->toArray() : (array)$order;
                $detail = is_object($detail) && method_exists($detail, 'toArray') ? $detail->toArray() : (array)$detail;
                $wcOrderNo = $this->orderDetailSync2Wc($order, $detail);
                $this->updateSaleOrdersDetails(
                    ['wc_order_no' => json_encode($wcOrderNo, JSON_UNESCAPED_UNICODE)],
                    ['sod_id' => $detail['sod_id']]
                );
                $failure = $this->getWcOrderSyncFailure($wcOrderNo);
                if ($failure === '') {
                    $service->markTaskSuccess($task, $wcOrderNo);
                    $result['success']++;
                } else {
                    $service->markRetryFailure($task, $failure, $wcOrderNo);
                    $result['failed']++;
                    if (intval($task['retry_count']) + 1 >= intval($task['max_retry_count'])) $result['final']++;
                }
            } catch (\Throwable $e) {
                $service->markRetryFailure($task, $e->getMessage());
                $result['failed']++;
                if (intval($task['retry_count']) + 1 >= intval($task['max_retry_count'])) $result['final']++;
                actionException($e, 1, 'retryWcOrderSync');
            }
        }
        foreach ($service->getPendingFinalNotices(50) as $task) {
            $this->sendFinalFailureNotice($task, $service);
        }
        return "处理完成：总数{$result['total']}，成功{$result['success']}，失败{$result['failed']}，转人工{$result['final']}";
    }

    protected function sendFinalFailureNotice($task, WcOrderSyncRetryService $service)
    {
        if (!$task || !$service->lockFinalNotice($task['wcst_id'])) return;
        $machine = $this->getMachineFind(['m_id' => intval($task['m_id'] ?? 0)]);
        $machine = $machine && is_object($machine) ? $machine->toArray() : (array)$machine;
        $error = mb_substr((string)($task['last_error'] ?? '未知错误'), 0, 100, 'UTF-8');
        $notice = [
            'sendType' => 1,
            'ao_id' => intval($task['ao_id'] ?? ($machine['ao_id'] ?? 0)),
            'm_id' => intval($task['m_id'] ?? 0),
            'templateType' => 'mFault',
            'replaceData' => [
                'errorCode' => '微程订单同步失败',
                'error_code' => 'WC_ORDER_SYNC_FAIL',
                'error_info' => 'WC_ORDER_SYNC_FAIL',
                'error_time' => date('Y-m-d H:i:s'),
                'date' => date('Y年m月d日'),
                'exceptionDeclaration' => '订单' . ($task['trade_no'] ?? '') . '商品' . ($task['g_name'] ?? '') . '同步失败：' . $error,
                'machine_id' => $task['machine_id'] ?? ($machine['machine_id'] ?? ''),
                'machine_name' => mb_substr((string)($machine['machine_name'] ?? ($task['machine_id'] ?? '')), 0, 20, 'UTF-8'),
            ],
        ];
        try {
            $sendResult = AppFactory::notice($notice)->weChat->send();
            $service->markNoticeResult($task['wcst_id'], $sendResult !== false);
        } catch (\Throwable $e) {
            $service->markNoticeResult($task['wcst_id'], false);
            actionException($e, 1, 'wcOrderSyncFinalNotice');
        }
    }
}
