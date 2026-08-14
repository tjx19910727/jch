<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\RabbitMq\MqConsumer;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class DataUpload extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('DataUpload')
            ->setDescription('the comer command');
    }

    protected function execute(Input $input, Output $output)
    {
        $initialDelay = intval(config('rabbit_mq.consumer_reconnect_initial_delay') ?: 1);
        $maxDelay = intval(config('rabbit_mq.consumer_reconnect_max_delay') ?: 30);
        if ($initialDelay < 1) $initialDelay = 1;
        if ($maxDelay < $initialDelay) $maxDelay = $initialDelay;
        $retryDelay = $initialDelay;

        while (true) {
            $startedAt = time();
            try {
                $output->writeln('RabbitMQ消费者启动：queue=dataUpload_queue');
                $consumer = new MqConsumer();
                $consumer->dataUpload();
                throw new \RuntimeException('RabbitMQ消费者监听已意外结束');
            } catch (\Throwable $e) {
                try {
                    actionException($e, 1);
                } catch (\Throwable $logException) {
                    error_log('MQ reconnect log failed: ' . $logException->getMessage());
                }
                $output->writeln('RabbitMQ消费者异常退出：queue=dataUpload_queue，运行时长=' . (time() - $startedAt)
                    . '秒，' . $retryDelay . '秒后重连：' . $e->getMessage());
            }

            // 稳定运行超过一分钟后从最短退避重新开始。
            if (time() - $startedAt >= 60) $retryDelay = $initialDelay;
            sleep($retryDelay);
            $retryDelay = min($retryDelay * 2, $maxDelay);
        }
    }
}
