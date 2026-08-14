<?php

namespace app\AppFactory\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * 异步任务生产者。
 */
class AsyncTaskProducer
{
    /**
     * 将异步任务放入队列。
     *
     * @param string $taskType
     * @param array $payload
     * @return string
     */
    public static function publish($taskType, $payload = [])
    {
        $connection = null;
        $channel = null;
        try {
            $param = config('rabbit_mq.' . env('RabbitMq.config_name'));
            $amqpDetail = config('rabbit_mq.async_task_queue');
            if (!$param || !$amqpDetail) {
                throw new \RuntimeException('RabbitMQ configuration is incomplete');
            }

            $connection = MqConnectionFactory::create($param);
            $channel = $connection->channel();

            $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
            $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
            $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);

            $data = [
                'task_id' => uniqid('task_'),
                'task_type' => $taskType,
                'payload' => $payload,
            ];
            $messageBody = json_encode($data);
            $message = new AMQPMessage($messageBody, [
                'content_type' => 'text/plain',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            // 启用发布确认，避免消息静默丢失
            $channel->confirm_select();
            $channel->basic_publish($message, $amqpDetail['exchange_name'], $amqpDetail['route_key']);
            $channel->wait_for_pending_acks(5);

            return 'OK';
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $e->getMessage();
        } finally {
            if ($channel) {
                try {
                    $channel->close();
                } catch (\Throwable $e) {
                    actionException($e, 1);
                }
            }
            if ($connection) {
                try {
                    $connection->close();
                } catch (\Throwable $e) {
                    actionException($e, 1);
                }
            }
        }
    }
}
