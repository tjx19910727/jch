<?php

namespace app\AppFactory\RabbitMq;

use app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerFactory;
use app\AppFactory\RabbitMq\AsyncTask\WcGoodsSyncLock;
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
    const COMPLETED_TASK_CACHE_TTL = 604800;
    const COMPLETED_TASK_MEMORY_FALLBACK_LIMIT = 1000;

    /**
     * 持久缓存写失败时的进程内兜底，覆盖当前消费命令断线重连后的消息重投。
     *
     * @var array<string, int>
     */
    protected static $completedTaskMemoryFallback = [];

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
    protected $allowedTaskTypes = [];

    public function async_task_queue()
    {
        return $this->consumeQueue('async_task_queue', ['goods_update']);
    }

    public function wc_goods_sync_queue()
    {
        return $this->consumeQueue('wc_goods_sync_queue', ['wc_goods_sync']);
    }

    protected function consumeQueue($queueConfigKey, array $allowedTaskTypes)
    {
        $param = config('rabbit_mq.' . env('RabbitMq.config_name'));
        if (!$param) die('获取不到RabbitMQ连接配置参数');
        $amqpDetail = config('rabbit_mq.' . $queueConfigKey);
        if (!$amqpDetail) die('获取不到异步任务配置参数【' . $queueConfigKey . '】');

        $this->allowedTaskTypes = $allowedTaskTypes;
        $connection = MqConnectionFactory::create($param);
        $channel = $connection->channel();
        $heartbeatSender = null;
        $channel->basic_qos(0, 1, false);

        try {
            $heartbeatSender = $this->registerSignalHeartbeatSafely($connection);
            $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
            $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
            $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);
            $channel->basic_consume($amqpDetail['queue_name'], $amqpDetail['consumer_tag'], false, false, false, false, [$this, 'process_message']);
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
     * PCNTL不可用或信号心跳注册失败时安全降级，不影响消费者启动。
     *
     * @param AMQPStreamConnection $connection
     * @return PCNTLHeartbeatSender|null
     */
    protected function registerSignalHeartbeatSafely(AMQPStreamConnection $connection)
    {
        if (!$this->supportsSignalHeartbeat()) {
            $this->logHeartbeatWarning('RabbitMQ async signal heartbeat is unavailable; consumer continues without signal heartbeat');
            return null;
        }

        $heartbeatSender = null;
        try {
            $heartbeatSender = new PCNTLHeartbeatSender($connection);
            $heartbeatSender->register();
            return $heartbeatSender;
        } catch (\Throwable $e) {
            if ($heartbeatSender) {
                try {
                    $heartbeatSender->unregister();
                } catch (\Throwable $unregisterException) {
                    error_log('RabbitMQ async heartbeat cleanup failed: ' . $unregisterException->getMessage());
                }
            }

            $this->logHeartbeatWarning('RabbitMQ async signal heartbeat registration failed; consumer continues without it: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 判断当前运行环境是否具备信号心跳所需的全部能力。
     */
    protected function supportsSignalHeartbeat()
    {
        if (PHP_SAPI !== 'cli'
            || (defined('AMQP_WITHOUT_SIGNALS') && constant('AMQP_WITHOUT_SIGNALS'))
            || !class_exists(PCNTLHeartbeatSender::class)
            || !extension_loaded('pcntl')
            || !defined('SIGALRM')
            || !defined('SIG_IGN')) {
            return false;
        }

        foreach (['pcntl_async_signals', 'pcntl_signal', 'pcntl_alarm'] as $function) {
            if (!$this->isFunctionEnabled($function)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 兼容扩展已加载、但运维通过disable_functions禁用单个PCNTL函数的场景。
     */
    protected function isFunctionEnabled($function)
    {
        if (!function_exists($function) || !is_callable($function)) {
            return false;
        }

        $disabledFunctions = function_exists('ini_get') ? strval(ini_get('disable_functions')) : '';
        if ($disabledFunctions === '') {
            return true;
        }

        $disabledFunctions = array_map('trim', explode(',', strtolower($disabledFunctions)));
        return !in_array(strtolower($function), $disabledFunctions, true);
    }

    protected function logHeartbeatWarning($message)
    {
        try {
            actionLog($message, 'RabbitMQ异步任务消费者心跳告警', 'async_task_consumer');
        } catch (\Throwable $e) {
            try {
                Log::write($message, 'warning');
            } catch (\Throwable $logException) {
                error_log($message);
            }
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
        $taskId = '';
        $taskType = '';
        try {
            $data = json2arr($message->body);
            if (!is_array($data)) throw new \InvalidArgumentException('异步任务消息格式不正确');
            $taskId = strval($data['task_id'] ?? '');
            $taskType = strval($data['task_type'] ?? '');
            if ($taskId === '') throw new \InvalidArgumentException('异步任务缺少task_id');
            if ($this->allowedTaskTypes && !in_array($taskType, $this->allowedTaskTypes, true)) {
                throw new \InvalidArgumentException('当前队列不支持任务类型：' . $taskType);
            }

            actionLog([
                'task_id' => $taskId,
                'task_type' => $taskType,
            ], '异步任务消息处理摘要', 'async_task_message');
            if ($this->isTaskCompleted($taskType, $taskId)) {
                actionLog([
                    'task_id' => $taskId,
                    'task_type' => $taskType,
                ], '异步任务已完成，跳过重复执行', 'async_task_message');
                $message->ack();
                return;
            }

            $handler = AsyncTaskHandlerFactory::make($taskType);
            $result = $handler->handle($data['payload'] ?? [], $data);
            $completionCachePersisted = $this->markTaskCompleted($taskType, $taskId);
            actionLog([
                'task_id' => $taskId,
                'task_type' => $taskType,
                'completion_cache_persisted' => $completionCachePersisted ? 1 : 0,
                'result' => $result,
            ], '异步任务处理结果', 'async_task_message');
            $message->ack();
        } catch (\Throwable $e) {
            $this->logProcessException($e);
            if ($this->isPermanentException($e) || $message->isRedelivered()) {
                if ($taskType === 'wc_goods_sync') {
                    WcGoodsSyncLock::release($taskId);
                }
                $message->ack();
                return;
            }
            $message->nack(true);
        }
    }

    protected function isPermanentException(\Throwable $e)
    {
        return $e instanceof \InvalidArgumentException || $e instanceof \DomainException;
    }

    /**
     * 完成缓存只用于耗时的微程同步任务，不改变其他异步任务的消费语义。
     */
    protected function isTaskCompleted($taskType, $taskId)
    {
        if (!$this->usesCompletedTaskCache($taskType, $taskId)) {
            return false;
        }

        $cacheKey = $this->completedTaskCacheKey($taskType, $taskId);
        if (isset(self::$completedTaskMemoryFallback[$cacheKey])) {
            if (self::$completedTaskMemoryFallback[$cacheKey] > time()) {
                return true;
            }
            unset(self::$completedTaskMemoryFallback[$cacheKey]);
        }

        try {
            return (bool)Cache::get($cacheKey);
        } catch (\Throwable $e) {
            error_log('RabbitMQ async completed cache read failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function markTaskCompleted($taskType, $taskId)
    {
        if (!$this->usesCompletedTaskCache($taskType, $taskId)) {
            return true;
        }

        $cacheKey = $this->completedTaskCacheKey($taskType, $taskId);
        $lastError = 'Cache::set returned false';
        try {
            if (Cache::set($cacheKey, 1, self::COMPLETED_TASK_CACHE_TTL)) {
                unset(self::$completedTaskMemoryFallback[$cacheKey]);
                return true;
            }
        } catch (\Throwable $e) {
            $lastError = $e->getMessage();
        }

        if (!isset(self::$completedTaskMemoryFallback[$cacheKey])
            && count(self::$completedTaskMemoryFallback) >= self::COMPLETED_TASK_MEMORY_FALLBACK_LIMIT) {
            array_shift(self::$completedTaskMemoryFallback);
        }
        self::$completedTaskMemoryFallback[$cacheKey] = time() + self::COMPLETED_TASK_CACHE_TTL;

        $warning = [
            'task_id' => $taskId,
            'task_type' => $taskType,
            'error' => $lastError,
            'fallback' => 'process_memory',
        ];
        try {
            actionLog($warning, '异步任务完成缓存写入失败，已使用进程内兜底', 'async_task_message');
        } catch (\Throwable $e) {
            error_log('RabbitMQ async completed cache write failed: ' . json_encode($warning));
        }

        return false;
    }

    protected function usesCompletedTaskCache($taskType, $taskId)
    {
        return $taskType === 'wc_goods_sync' && $taskId !== '';
    }

    protected function completedTaskCacheKey($taskType, $taskId)
    {
        return 'async_task_completed_' . hash('sha256', strval($taskType) . ':' . strval($taskId));
    }

    /**
     * 日志异常不能阻止消息进入最后一次ACK流程。
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
