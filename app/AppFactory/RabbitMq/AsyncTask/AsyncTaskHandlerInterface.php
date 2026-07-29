<?php

namespace app\AppFactory\RabbitMq\AsyncTask;

/**
 * 异步任务处理器接口。
 */
interface AsyncTaskHandlerInterface
{
    /**
     * @param array $payload
     * @param array $task
     */
    public function handle($payload, $task = []);
}
