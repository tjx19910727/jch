<?php

namespace app\AppFactory\TimeTask\WeiCheng;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Service\WeiCheng\WcOrderSyncRetryService;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\RabbitMq\AsyncTaskProducer;
use app\AppFactory\TimeTask\TimeTaskBase;

class WeiChengClient extends TimeTaskBase
{
    use WcBaseTrait, MachineTrait;

    /**
     * 投递微程商品全量同步任务，由异步任务消费者执行实际同步。
     */
    public function syncGoodsAll()
    {
        $result = AsyncTaskProducer::publish('wc_goods_sync', [
            'request_time' => date('Y-m-d H:i:s'),
            'source' => 'time_task',
            'goods_type' => '',
        ]);
        if ($result !== 'OK') {
            return '微程商品全量同步任务提交失败：' . $result;
        }
        return '微程商品全量同步任务提交成功';
    }

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

    /**
     * 清理 N 小时前的微程商品同步日志，默认保留 24 小时（每日执行一次）。
     * 命令：php think time_task weiCheng cleanGoodsSyncLogs
     */
    public function cleanGoodsSyncLogs($retainHours = 24)
    {
        $retainHours = max(1, intval($retainHours));
        $deadline = date('Y-m-d H:i:s', time() - $retainHours * 3600);
        $count = $this->deleteWcGoodsSyncLogBefore($deadline);
        actionLog(['retain_hours' => $retainHours, 'deadline' => $deadline, 'deleted' => $count], '清理微程商品同步日志', 'wc_goods_sync_log_clean');
        return "清理完成：删除 {$count} 条微程商品同步日志";
    }

    /**
     * 补齐线上与实物货道二维码，建议每分钟执行一次。
     * 每轮请求数受 limit 限制，内部按微程接口频率串行调用。
     */
    public function repairGoodsQrCodes($limit = 20)
    {
        $limit = max(1, intval($limit));
        $wcLimit = max(1, intval(ceil($limit / 2)));
        $wcResult = $this->syncWcMachineChannelQrCodes([], $wcLimit, 'time_task_repair');
        $remaining = max(0, $limit - intval($wcResult['requested']));

        $physicalResult = ['total' => 0, 'requested' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'rate_limited' => false];
        if ($remaining > 0) {
            if (intval($wcResult['requested']) > 0) {
                usleep(1100000);
            }
            $physicalResult = $this->syncPhysicalMachineChannelQrCodes([], $remaining, 'time_task_repair');
        }

        $result = ['wc_machine_channel' => $wcResult, 'machine_channel' => $physicalResult];
        actionLog($result, '补齐货道商品小程序码', 'wc_goods_qrcode_repair');
        return '处理完成：' . json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function repairWcMachineChannelQrCodes($limit = 20)
    {
        $result = $this->syncWcMachineChannelQrCodes([], $limit, 'time_task_wc_repair');
        return '处理完成：' . json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function repairPhysicalMachineChannelQrCodes($limit = 20)
    {
        $result = $this->syncPhysicalMachineChannelQrCodes([], $limit, 'time_task_physical_repair');
        return '处理完成：' . json_encode($result, JSON_UNESCAPED_UNICODE);
    }

}
