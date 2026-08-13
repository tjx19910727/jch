<?php

declare(strict_types=1);

namespace app\command;

use app\AppFactory\RabbitMq\AsyncTaskConsumer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * RabbitMQ异步任务消费命令。
 */
class AsyncTaskQueue extends Command
{
    protected function configure()
    {
        $this->setName('AsyncTaskQueue')
            ->setDescription('RabbitMQ异步任务消费者');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指数退避重连：断线后自动拉起，避免异步任务丢失
        $initialDelay = intval(config('rabbit_mq.consumer_reconnect_initial_delay') ?: 1);
        $maxDelay = intval(config('rabbit_mq.consumer_reconnect_max_delay') ?: 30);
        if ($initialDelay < 1) $initialDelay = 1;
        if ($maxDelay < $initialDelay) $maxDelay = $initialDelay;
        $retryDelay = $initialDelay;

        while (true) {
            $startedAt = time();
            try {
                $output->writeln('RabbitMQ消费者启动：queue=async_task_queue');
                $consumer = new AsyncTaskConsumer();
                $consumer->async_task_queue();
                throw new \RuntimeException('RabbitMQ消费者监听已意外结束');
            } catch (\Throwable $e) {
                try {
                    actionException($e, 1);
                } catch (\Throwable $logException) {
                    error_log('MQ reconnect log failed: ' . $logException->getMessage());
                }
                $output->writeln('RabbitMQ消费者异常退出：queue=async_task_queue，运行时长=' . (time() - $startedAt)
                    . '秒，' . $retryDelay . '秒后重连：' . $e->getMessage());
            }

            // 稳定运行超过一分钟后从最短退避重新开始。
            if (time() - $startedAt >= 60) $retryDelay = $initialDelay;
            sleep($retryDelay);
            $retryDelay = min($retryDelay * 2, $maxDelay);
        }
    }
}
