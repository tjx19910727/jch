<?php

namespace app\AppFactory\Kernel\Service\WeiCheng;

use think\facade\Db;

class WcOrderSyncRetryService
{
    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_RETRY = 3;
    const STATUS_MANUAL = 4;

    const NOTICE_PENDING = 0;
    const NOTICE_SUCCESS = 1;
    const NOTICE_FAILED = 2;
    const NOTICE_RUNNING = 3;

    /** 首次同步失败后创建或重置任务，定时任务最多再重试3次。 */
    public function enqueue($order, $detail, $error, $response = [], $resetFinal = false)
    {
        $order = $this->normalizeArray($order);
        $detail = $this->normalizeArray($detail);
        $orderId = intval(isset($order['order_id']) ? $order['order_id'] : 0);
        $sodId = intval(isset($detail['sod_id']) ? $detail['sod_id'] : 0);
        if ($orderId <= 0 || $sodId <= 0) return false;

        $now = time();
        $where = ['sod_id' => $sodId, 'request_type' => 1];
        $data = [
            'order_id' => $orderId,
            'sod_id' => $sodId,
            'idempotency_key' => (isset($order['trade_no']) ? $order['trade_no'] : $orderId) . '#' . $sodId,
            'status' => self::STATUS_RETRY,
            'retry_count' => 0,
            'max_retry_count' => 3,
            'next_retry_time' => $now + 60,
            'last_error' => mb_substr((string)$error, 0, 1000, 'UTF-8'),
            'response_payload' => json_encode($response, JSON_UNESCAPED_UNICODE),
            'notice_status' => self::NOTICE_PENDING,
            'update_time' => $now,
        ];
        $task = Db::name('wc_order_sync_task')->where($where)->find();
        if ($task) {
            // 成功、执行中或已转人工的任务不被重复支付回调重置。
            if (intval($task['status']) === self::STATUS_RUNNING) return true;
            if (intval($task['status']) === self::STATUS_SUCCESS && !$resetFinal) return true;
            if (intval($task['status']) === self::STATUS_MANUAL && !$resetFinal) return true;
            $data['retry_count'] = $resetFinal ? 0 : intval($task['retry_count']);
            $data['max_retry_count'] = max(1, intval($task['max_retry_count']));
            $data['notice_status'] = $resetFinal ? self::NOTICE_PENDING : intval($task['notice_status']);
            if (!$resetFinal && intval($task['next_retry_time']) > 0) {
                $data['next_retry_time'] = intval($task['next_retry_time']);
            }
            return Db::name('wc_order_sync_task')->where(['wcst_id' => $task['wcst_id']])->update($data) !== false;
        }
        $data['request_type'] = 1;
        $data['create_time'] = $now;
        return Db::name('wc_order_sync_task')->insert($data) !== false;
    }

    public function markSuccessBySodId($sodId, $response = [])
    {
        return Db::name('wc_order_sync_task')
            ->where(['sod_id' => intval($sodId), 'request_type' => 1])
            ->update([
                'status' => self::STATUS_SUCCESS,
                'last_error' => '',
                'response_payload' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'next_retry_time' => 0,
                'update_time' => time(),
            ]);
    }

    /** 手动推送前预占已有任务；定时任务正在执行时拒绝并发推送。 */
    public function reserveManualPush($sodId)
    {
        $task = Db::name('wc_order_sync_task')
            ->where(['sod_id' => intval($sodId), 'request_type' => 1])
            ->find();
        if (!$task) return true;
        if (intval($task['status']) === self::STATUS_RUNNING) return false;
        return Db::name('wc_order_sync_task')
            ->where(['wcst_id' => $task['wcst_id']])
            ->where('status', '<>', self::STATUS_RUNNING)
            ->update([
                'status' => self::STATUS_RETRY,
                'next_retry_time' => time() + 300,
                'update_time' => time(),
            ]) === 1;
    }

    /** 释放进程异常退出后遗留的执行中任务。 */
    public function releaseStaleTasks()
    {
        return Db::name('wc_order_sync_task')
            ->where('status', self::STATUS_RUNNING)
            ->where('update_time', '<=', time() - 600)
            ->update([
                'status' => self::STATUS_RETRY,
                'next_retry_time' => time(),
                'last_error' => '重试任务执行超时，已重新入队',
                'update_time' => time(),
            ]);
    }

    /** 原子领取到期任务，防止多进程重复执行。 */
    public function claimDueTasks($limit = 50)
    {
        $this->releaseStaleTasks();
        $rows = Db::name('wc_order_sync_task')
            ->where('status', 'in', [self::STATUS_PENDING, self::STATUS_RETRY])
            ->where('next_retry_time', '<=', time())
            ->order('wcst_id asc')
            ->limit(max(1, intval($limit)))
            ->select();
        $rows = is_object($rows) && method_exists($rows, 'toArray') ? $rows->toArray() : (array)$rows;
        $claimed = [];
        foreach ($rows as $row) {
            $affected = Db::name('wc_order_sync_task')
                ->where(['wcst_id' => $row['wcst_id']])
                ->where('status', 'in', [self::STATUS_PENDING, self::STATUS_RETRY])
                ->update(['status' => self::STATUS_RUNNING, 'update_time' => time()]);
            if ($affected === 1) $claimed[] = $row;
        }
        return $claimed;
    }

    /** 每次定时执行算一次重试；第3次仍失败后转人工处理。 */
    public function markRetryFailure($task, $error, $response = [])
    {
        $task = $this->normalizeArray($task);
        $retryCount = intval(isset($task['retry_count']) ? $task['retry_count'] : 0) + 1;
        $maxRetryCount = max(1, intval(isset($task['max_retry_count']) ? $task['max_retry_count'] : 3));
        $isFinal = $retryCount >= $maxRetryCount;
        $delayMap = [1 => 60, 2 => 300, 3 => 900];
        $delay = isset($delayMap[$retryCount]) ? $delayMap[$retryCount] : 900;
        return Db::name('wc_order_sync_task')
            ->where(['wcst_id' => $task['wcst_id'], 'status' => self::STATUS_RUNNING])
            ->update([
                'status' => $isFinal ? self::STATUS_MANUAL : self::STATUS_RETRY,
                'retry_count' => $retryCount,
                'next_retry_time' => $isFinal ? 0 : time() + $delay,
                'last_error' => mb_substr((string)$error, 0, 1000, 'UTF-8'),
                'response_payload' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'notice_status' => $isFinal ? self::NOTICE_PENDING : intval(isset($task['notice_status']) ? $task['notice_status'] : 0),
                'update_time' => time(),
            ]);
    }

    public function markTaskSuccess($task, $response = [])
    {
        $task = $this->normalizeArray($task);
        return Db::name('wc_order_sync_task')
            ->where(['wcst_id' => $task['wcst_id'], 'status' => self::STATUS_RUNNING])
            ->update([
                'status' => self::STATUS_SUCCESS,
                'last_error' => '',
                'response_payload' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'next_retry_time' => 0,
                'update_time' => time(),
            ]);
    }

    public function getPendingFinalNotices($limit = 50)
    {
        $rows = Db::name('wc_order_sync_task')->alias('t')
            ->leftJoin('sale_orders o', 'o.order_id=t.order_id')
            ->leftJoin('sale_orders_details d', 'd.sod_id=t.sod_id')
            ->where(['t.status' => self::STATUS_MANUAL, 't.notice_status' => self::NOTICE_PENDING])
            ->field('t.*,o.trade_no,o.m_id,o.machine_id,o.ao_id,d.g_name,d.wc_order_no')
            ->order('t.wcst_id asc')
            ->limit(max(1, intval($limit)))
            ->select();
        return is_object($rows) && method_exists($rows, 'toArray') ? $rows->toArray() : (array)$rows;
    }

    public function lockFinalNotice($taskId)
    {
        return Db::name('wc_order_sync_task')
            ->where(['wcst_id' => intval($taskId), 'status' => self::STATUS_MANUAL, 'notice_status' => self::NOTICE_PENDING])
            ->update(['notice_status' => self::NOTICE_RUNNING, 'update_time' => time()]) === 1;
    }

    public function markNoticeResult($taskId, $success)
    {
        return Db::name('wc_order_sync_task')
            ->where(['wcst_id' => intval($taskId), 'notice_status' => self::NOTICE_RUNNING])
            ->update(['notice_status' => $success ? self::NOTICE_SUCCESS : self::NOTICE_FAILED, 'update_time' => time()]);
    }

    private function normalizeArray($value)
    {
        if (is_object($value)) return method_exists($value, 'toArray') ? $value->toArray() : (array)$value;
        return is_array($value) ? $value : (array)$value;
    }
}
