<?php

namespace app\AppFactory\RabbitMq\AsyncTask\Handler;

use app\AppFactory\AppFactory;
use app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerInterface;
use app\AppFactory\RabbitMq\AsyncTask\WcGoodsSyncLock;

/**
 * 微程商品同步任务。
 */
class WcGoodsSyncHandler implements AsyncTaskHandlerInterface
{
    /**
     * @param array $payload
     * @param array $task
     */
    public function handle($payload, $task = [])
    {
        $taskId = strval($task['task_id'] ?? '');
        if ($taskId === '') throw new \InvalidArgumentException('微程商品同步缺少task_id');
        if (!WcGoodsSyncLock::refresh($taskId) && !WcGoodsSyncLock::acquire($taskId)) {
            throw new \DomainException('已有其他微程商品同步任务正在执行');
        }

        $app = AppFactory::management();
        $goodsType = intval($payload['goods_type'] ?? 0);
        $syncBatchNo = substr(hash('sha256', $taskId), 0, 20);
        $completed = false;
        try {
            if ($goodsType) {
                $syncRes = $app->weicheng->synchronizeGoodsTypes($goodsType, 1, $syncBatchNo, true);
            } else {
                $syncRes = $app->weicheng->synchronizeGoodsTypesAll($syncBatchNo);
            }
            $syncResLog = $this->normalizeResponse($syncRes);
            actionLog([
                'task_id' => $taskId,
                'sync_batch_no' => $syncBatchNo,
                'result' => $syncResLog,
            ], '微程分类同步结果', 'async_task_wc_goods_sync');
            if (intval($syncResLog['state'] ?? 0) !== 200) {
                throw new \RuntimeException('微程商品列表同步失败');
            }

            WcGoodsSyncLock::refresh($taskId);
            $result = $app->weicheng->synchronizeGoodsAll($syncBatchNo, $taskId);
            $resultLog = $this->normalizeResponse($result);
            actionLog([
                'task_id' => $taskId,
                'sync_batch_no' => $syncBatchNo,
                'result' => $resultLog,
            ], '微程同步处理结果', 'async_task_wc_goods_sync');
            if (intval($resultLog['state'] ?? 0) !== 200) {
                throw new \RuntimeException('微程商品详情同步存在失败');
            }
            $completed = true;
            return $result;
        } finally {
            if ($completed) WcGoodsSyncLock::release($taskId);
        }
    }

    protected function normalizeResponse($result)
    {
        if (is_array($result)) return $result;
        if (is_object($result) && method_exists($result, 'getData')) {
            $data = $result->getData();
            return is_object($data) ? json_decode(json_encode($data), true) : (array)$data;
        }
        if (is_object($result) && method_exists($result, 'getContent')) {
            return json2arr($result->getContent()) ?: [];
        }
        return [];
    }
}
