<?php

namespace app\AppFactory\RabbitMq;

use app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Log;

/**
 * 异步任务消费者。
 */
class AsyncTaskConsumer
{
    /**
     * 关闭RabbitMQ连接。
     *
     * @param AMQPChannel $channel
     * @param AMQPStreamConnection $connection
     * @throws \Exception
     */
    public function shutdown(AMQPChannel $channel, AMQPStreamConnection $connection)
    {
        $channel->close();
        $connection->close();
        Log::write('closed', 3);
    }

    /**
     * 监听异步任务队列。
     *
     * @throws \Exception
     */
    public function async_task_queue()
    {
        $param = config('rabbit_mq.' . env('RabbitMq.config_name'));
        if (!$param) {
            die('获取不到RabbitMQ【' . env('RabbitMq.config_name') . "】的连接配置参数 \n");
        }

        $amqpDetail = config('rabbit_mq.async_task_queue');
        if (!$amqpDetail) {
            die("获取不到异步任务相关配置参数【async_task_queue】 \n");
        }

        $connection = MqConnectionFactory::create($param);
        $channel = $connection->channel();
        $channel->basic_qos(0, 1, false);

        $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
        $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
        $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);

        $channel->basic_consume(
            $amqpDetail['queue_name'],
            $amqpDetail['consumer_tag'],
            false,
            false,
            false,
            false,
            [$this, 'process_message']
        );
        try {
            while (count($channel->callbacks)) {
                $channel->wait();
            }
        } finally {
            $this->shutdownSafely($channel, $connection);
        }
    }

    /**
     * 每轮消费结束立即释放连接，避免连接假死时泄漏与关闭异常。
     */
    protected function shutdownSafely(AMQPChannel $channel, AMQPStreamConnection $connection)
    {
        try {
            if ($channel->is_open()) $channel->close();
        } catch (\Throwable $e) {
            error_log('RabbitMQ async channel close failed: ' . $e->getMessage());
        }
        try {
            if ($connection->isConnected()) $connection->close();
        } catch (\Throwable $e) {
            error_log('RabbitMQ async connection close failed: ' . $e->getMessage());
        }
        try {
            Log::write('closed', 3);
        } catch (\Throwable $e) {
            error_log('RabbitMQ async close log failed: ' . $e->getMessage());
        }
    }

    /**
     * 处理异步任务消息。
     *
     * @param AMQPMessage $message
     */
    public function process_message(AMQPMessage $message)
    {
        $data = [];
        try {
            $data = json2arr($message->body);
            $taskType = $data['task_type'] ?? '';

            actionLog([
                'task_id' => $data['task_id'] ?? '',
                'task_type' => $taskType,
            ], '异步任务消息处理摘要', 'async_task_message');

            $handler = AsyncTaskHandlerFactory::make($taskType);
            $result = $handler->handle($data['payload'] ?? [], $data);

            actionLog([
                'task_id' => $data['task_id'] ?? '',
                'task_type' => $taskType,
                'result' => $result,
            ], '异步任务处理结果', 'async_task_message');

            $message->ack();
        } catch (\Throwable $e) {
            actionLog(
                $e->getFile() . '_' . $e->getLine() . '_' . $e->getMessage(),
                'tryCatchMessage',
                'async_task_message'
            );
            actionLog($e->getTrace(), 'tryCatchTrace', 'async_task_message');
            $message->ack();
        }
    }
}
