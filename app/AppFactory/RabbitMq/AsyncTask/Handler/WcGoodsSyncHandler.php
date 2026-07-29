<?php

namespace app\AppFactory\RabbitMq\AsyncTask\Handler;

use app\AppFactory\AppFactory;
use app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerInterface;

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
        $app = AppFactory::management();
        $goodsType = $payload['goods_type'] ?? '';

        if ($goodsType) {
            $syncRes = $app->weicheng->synchronizeGoodsTypes($goodsType);
        } else {
            $syncRes = $app->weicheng->synchronizeGoodsTypesAll();
        }

        $syncResLog = $syncRes;
        if (is_object($syncRes) && method_exists($syncRes, 'getData')) {
            $syncResLog = $syncRes->getData();
        } elseif (is_object($syncRes) && method_exists($syncRes, 'getContent')) {
            $syncResLog = json2arr($syncRes->getContent());
        }
        actionLog([
            'task_id' => $task['task_id'] ?? '',
            'result' => $syncResLog,
        ], '微程分类同步结果', 'async_task_wc_goods_sync');

        $result = $app->weicheng->synchronizeGoodsAll();
        $resultLog = $result;
        if (is_object($result) && method_exists($result, 'getData')) {
            $resultLog = $result->getData();
        } elseif (is_object($result) && method_exists($result, 'getContent')) {
            $resultLog = json2arr($result->getContent());
        }
        actionLog([
            'task_id' => $task['task_id'] ?? '',
            'result' => $resultLog,
        ], '微程同步处理结果', 'async_task_wc_goods_sync');

        return $result;
    }
}
