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
        try {
            $consumer = new AsyncTaskConsumer();
            $consumer->async_task_queue();
        } catch (\Throwable $e) {
            actionException($e, 1);
            $output->writeln($e->getMessage());
        }
    }
}
