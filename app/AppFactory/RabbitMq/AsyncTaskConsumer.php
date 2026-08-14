<?php

namespace app\AppFactory\RabbitMq;

use app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerFactory;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Cache;
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
        $heartbeatSender = null;
        $channel->basic_qos(0, 1, false);

        try {
            if ($this->supportsSignalHeartbeat()) {
                $heartbeatSender = new PCNTLHeartbeatSender($connection);
                $heartbeatSender->register();
            } else {
                Log::warning('RabbitMQ async signal heartbeat is unavailable; install/enable pcntl for long-running tasks');
            }

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

            while (count($channel->callbacks)) {
                $channel->wait();
            }
        } finally {
            if ($heartbeatSender) {
                try {
                    $heartbeatSender->unregister();
                } catch (\Throwable $e) {
                    error_log('RabbitMQ async heartbeat unregister failed: ' . $e->getMessage());
                }
            }
            $this->shutdownSafely($channel, $connection);
        }
    }

    /**
     * 长任务执行期间通过信号维持 AMQP 连接心跳。
     */
    protected function supportsSignalHeartbeat()
    {
        $signalsEnabled = !defined('AMQP_WITHOUT_SIGNALS')
            || !constant('AMQP_WITHOUT_SIGNALS');

        return extension_loaded('pcntl')
            && function_exists('pcntl_async_signals')
            && $signalsEnabled;
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
            $taskId = $data['task_id'] ?? '';
            $taskType = $data['task_type'] ?? '';

            actionLog([
                'task_id' => $taskId,
                'task_type' => $taskType,
            ], '异步任务消息处理摘要', 'async_task_message');

            if ($this->isTaskCompleted($taskId)) {
                actionLog([
                    'task_id' => $taskId,
                    'task_type' => $taskType,
                ], '异步任务已完成，跳过重复执行', 'async_task_message');
            } else {
                $handler = AsyncTaskHandlerFactory::make($taskType);
                $result = $handler->handle($data['payload'] ?? [], $data);
                $this->markTaskCompleted($taskId);

                actionLog([
                    'task_id' => $taskId,
                    'task_type' => $taskType,
                    'result' => $result,
                ], '异步任务处理结果', 'async_task_message');
            }
        } catch (\Throwable $e) {
            $this->logProcessException($e);
        }

        // ack 与业务异常分开；确认失败时由外层重连，禁止在失效连接上重复 ack。
        $message->ack();
    }

    /**
     * 已完成标记用于避免“业务成功但 ack 丢失”造成的重复执行。
     */
    protected function isTaskCompleted($taskId)
    {
        if (!$taskId) return false;

        try {
            return (bool)Cache::get($this->completedTaskCacheKey($taskId));
        } catch (\Throwable $e) {
            error_log('RabbitMQ async completed cache read failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function markTaskCompleted($taskId)
    {
        if (!$taskId) return;

        try {
            Cache::set($this->completedTaskCacheKey($taskId), 1, 7 * 24 * 3600);
        } catch (\Throwable $e) {
            error_log('RabbitMQ async completed cache write failed: ' . $e->getMessage());
        }
    }

    protected function completedTaskCacheKey($taskId)
    {
        return 'async_task_completed_' . hash('sha256', strval($taskId));
    }

    /**
     * 日志异常不能阻止最后一次且仅一次的消息确认。
     */
    protected function logProcessException(\Throwable $e)
    {
        try {
            actionLog(
                $e->getFile() . '_' . $e->getLine() . '_' . $e->getMessage(),
                'tryCatchMessage',
                'async_task_message'
            );
            actionLog($e->getTrace(), 'tryCatchTrace', 'async_task_message');
        } catch (\Throwable $logException) {
            error_log('RabbitMQ async process exception log failed: ' . $logException->getMessage());
        }
    }
}
