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

        $connection = new AMQPStreamConnection(
            $param['host'],
            $param['port'],
            $param['login'],
            $param['password'],
            $param['vhost']
        );
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
        register_shutdown_function([$this, 'shutdown'], $channel, $connection);

        while (count($channel->callbacks)) {
            $channel->wait();
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

            $message->ack($message->getDeliveryTag());
        } catch (\Throwable $e) {
            actionLog(
                $e->getFile() . '_' . $e->getLine() . '_' . $e->getMessage(),
                'tryCatchMessage',
                'async_task_message'
            );
            actionLog($e->getTrace(), 'tryCatchTrace', 'async_task_message');
            $message->ack($message->getDeliveryTag());
        }
    }
}
