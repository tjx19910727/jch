<?php

namespace app\AppFactory\RabbitMq\AsyncTask;

use app\AppFactory\RabbitMq\AsyncTask\Handler\GoodsUpdateHandler;
use app\AppFactory\RabbitMq\AsyncTask\Handler\WcGoodsSyncHandler;

/**
 * 异步任务处理器简单工厂。
 */
class AsyncTaskHandlerFactory
{
    protected static $handlers = [
        'wc_goods_sync' => WcGoodsSyncHandler::class,
        'goods_update' => GoodsUpdateHandler::class,
    ];

    /**
     * 根据任务类型创建处理器。
     *
     * @param string $taskType
     * @return AsyncTaskHandlerInterface
     */
    public static function make($taskType)
    {
        if (!$taskType) {
            throw new \InvalidArgumentException('异步任务缺少task_type');
        }

        $handlerClass = self::$handlers[$taskType] ?? '';
        if (!$handlerClass) {
            throw new \InvalidArgumentException('不支持的异步任务类型：' . $taskType);
        }

        $handler = new $handlerClass();
        if (!$handler instanceof AsyncTaskHandlerInterface) {
            throw new \RuntimeException($handlerClass . '未实现AsyncTaskHandlerInterface');
        }

        return $handler;
    }
}
