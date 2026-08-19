<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 16:05
 */

namespace app\AppFactory\RabbitMq;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Db;
use think\facade\Log;

class MqConsumer
{

    use MachineMqRecordTrait;

    /**
     *  消费端 消费端需要保持运行状态实现方式
     * @param AMQPChannel $channel
     * @param AMQPStreamConnection $connection
     * @throws \Exception
     */
    public function shutdown(AMQPChannel $channel, AMQPStreamConnection $connection)
    {
        $this->shutdownSafely($channel, $connection);
    }

    /**
     * 每轮消费结束立即释放连接，避免重连时累积shutdown回调和旧连接引用。
     */
    protected function shutdownSafely(AMQPChannel $channel, AMQPStreamConnection $connection)
    {
        try {
            if ($channel->is_open()) $channel->close();
        } catch (\Throwable $e) {
            error_log('RabbitMQ channel close failed: ' . $e->getMessage());
        }
        try {
            if ($connection->isConnected()) $connection->close();
        } catch (\Throwable $e) {
            error_log('RabbitMQ connection close failed: ' . $e->getMessage());
        }
        try {
            Log::write("closed", 3);
        } catch (\Throwable $e) {
            error_log('RabbitMQ close log failed: ' . $e->getMessage());
        }
    }

    // 验签失败时允许重试的最大次数（避免死循环）
    protected $signVerifyRetryMax = 3;
    // 验签失败时允许重试的关键业务消息类型
    protected $signVerifyRetryMsgTypes = ['outGoods', 'paySuccess', 'remoteOutGoods'];

    /**
     * 消息处理
     * @param $message
     */
    public function process_message(AMQPMessage $message)
    {
        $data = [];
        $alreadyProcessed = false;
        try {
            $data = $message->body;
            $data = json2arr($data);
            if (!is_array($data) || empty($data['msg_id']) || empty($data['machine_id'])) {
                throw new \InvalidArgumentException('MQ顶层数据缺少msg_id或machine_id');
            }
            if ($this->isAuthRequest($data)) {
                $this->logAuthStage($data, 'REQUEST_RECEIVED', [
                    'queue_wait_ms' => $this->getQueueWaitMilliseconds($data),
                    'redelivered' => $message->isRedelivered(),
                ]);
            }
            if (!empty($data['msg_id']) && !empty($data['machine_id'])) {
                $processed = $this->getMachineMqRecordFind([
                    'msg_id' => $data['msg_id'],
                    'machine_id' => $data['machine_id'],
                    'from' => 2,
                    'type' => 1,
                    'status' => 2,
                ]);
                if ($processed) {
                    $alreadyProcessed = true;
                }
            }
            if (!$alreadyProcessed && isset($data['data'])) {
                $actionData = json2arr($data['data']);
                if (isset($actionData['msgType']) && $actionData['msgType'] != "heartbeat")
                    $this->actionLogSafely($data, '消息处理', "DataUpload");
            }

            if (!$alreadyProcessed) {
                $config = [
                    "machine_id" => $data['machine_id'],
//                    "key" => cache($data['machine_id'] . ".signKey"),
                    "data" => $data,
                    "mac" => $data['mac'] ?? "",
                    "transport" => "mq",
                ];
                $app = AppFactory::machine($config);
                $result = $app->mq->onMessage();
                if (isset($data['data'])) {
                    $actionData = json2arr($data['data']);
                    if (isset($actionData['msgType']) && $actionData['msgType'] != "heartbeat")
                        $this->actionLogSafely($result, '处理结果','DataUpload');
                }
            }
        } catch (\Throwable $e) {
            if ($this->isAuthRequest($data)) {
                $this->logAuthStage($data, 'CONSUME_FAILED', [
                    'error_type' => get_class($e),
                    'retryable' => !($e instanceof \InvalidArgumentException) && !$message->isRedelivered(),
                ]);
            }
            $this->logConsumerExceptionSafely($e);
            $this->recordMachineMqStatusSafely($data, 3);
            // 认证/格式错误默认属于永久错误；但验签失败的关键业务消息允许重试，
            // 避免设备认证恢复前 outGoods/paySuccess 等指令被直接丢弃。
            $isPermanent = $e instanceof \InvalidArgumentException;
            $retryableSignVerify = false;
            $msgType = '';
            if ($isPermanent && strpos($e->getMessage(), '验签') !== false && is_array($data) && !empty($data['data'])) {
                $actionData = json2arr($data['data']);
                $msgType = $actionData['msgType'] ?? '';
                if (in_array($msgType, $this->signVerifyRetryMsgTypes, true)) {
                    $retryableSignVerify = true;
                }
            }
            $isRedelivered = $message->isRedelivered();
            $requeue = $retryableSignVerify
                ? !$isRedelivered || $this->isRetryableRedelivery($data)
                : (!$isPermanent && !$isRedelivered);
            if ($requeue) {
                $this->actionLogSafely([
                    'msg_id' => $data['msg_id'] ?? '',
                    'machine_id' => $data['machine_id'] ?? '',
                    'msgType' => $msgType,
                    'error' => $e->getMessage(),
                    'redelivered' => $isRedelivered,
                ], 'MQ消息重试入队', 'DataUpload');
            }
            $message->nack($requeue, false);
            return;
        }

        if ($alreadyProcessed) {
            $this->actionLogSafely([
                'msg_id' => $data['msg_id'],
                'machine_id' => $data['machine_id'],
            ], '跳过已成功处理的MQ重投消息', 'DataUpload');
        } else {
            // 状态记录属于旁路审计，失败不能阻止已完成业务消息 ACK。
            $this->recordMachineMqStatusSafely($data, 2);
        }
        // ACK异常直接交给消费循环处理，关闭连接后由Broker重新投递。
        $message->ack();
    }

    /**
     * 无签名且携带MAC的消息为设备认证握手请求。
     */
    protected function isAuthRequest($data)
    {
        return is_array($data) && !empty($data['mac']) && empty($data['sign']);
    }

    /**
     * 认证日志只记录定位字段，不记录signKey或完整报文。
     */
    protected function logAuthStage($data, $stage, $extra = [])
    {
        if (!is_array($data)) return;
        $summary = array_merge([
            'auth_stage' => $stage,
            'machine_id' => $data['machine_id'] ?? '',
            'msg_id' => $data['msg_id'] ?? '',
        ], is_array($extra) ? $extra : []);
        $this->actionLogSafely($summary, 'MQ认证阶段', 'DataUploadAuth');
    }

    protected function getQueueWaitMilliseconds($data)
    {
        $timestamp = intval($data['timestamp'] ?? 0);
        if ($timestamp <= 0) return null;
        if ($timestamp < 100000000000) $timestamp *= 1000;
        return max(0, intval(round(microtime(true) * 1000)) - $timestamp);
    }

    /**
     * MQ记录失败不能打断消息确认流程。
     * 更新影响0行时尝试补录幂等记录，确保 status=2 可被后续重投命中，
     * 避免业务已执行但记录缺失导致消息重投后重复执行。
     *
     * @param array $data
     * @param int $status
     */
    protected function recordMachineMqStatusSafely($data, $status)
    {
        if (!is_array($data) || empty($data['msg_id']) || empty($data['machine_id'])) return;
        try {
            $result = $this->updateMachineMqRecord(
                ['status' => $status, 'msg_id' => $data['msg_id']],
                ['msg_id' => $data['msg_id'], 'machine_id' => $data['machine_id']]
            );
            $this->actionLogSafely($result, '修改MQ记录状态结果', 'DataUpload');

            // 更新影响0行说明记录未落库，补录带status的幂等记录防止重投重复执行
            if ($result === false || intval($result) === 0) {
                $existed = $this->getMachineMqRecordFind([
                    'msg_id' => $data['msg_id'],
                    'machine_id' => $data['machine_id'],
                ], 'mr_id');
                if (!$existed) {
                    $this->addMachineMqRecord([
                        'm_id' => intval($data['m_id'] ?? 0),
                        'machine_id' => $data['machine_id'],
                        'machine_name' => strval($data['machine_name'] ?? ''),
                        'msg_id' => $data['msg_id'],
                        'path' => strval($data['path'] ?? 'dataUpload'),
                        'content' => json_encode($data),
                        'from' => 2,
                        'type' => 1,
                        'status' => $status,
                    ]);
                    $this->actionLogSafely(['msg_id' => $data['msg_id']], 'MQ状态记录缺失已补录', 'DataUpload');
                }
            }
        } catch (\Throwable $e) {
            error_log('MQ record status update failed: ' . $e->getMessage());
        }
    }

    /**
     * 判断业务消息在多次 redelivery 后是否仍需继续重试（限制最大重试次数）。
     * @param array $data MQ消息数据
     * @return bool
     */
    protected function isRetryableRedelivery($data)
    {
        $retryCount = 0;
        $retryKey = '';
        try {
            if (is_array($data) && !empty($data['msg_id'])) {
                $retryKey = 'mq_verify_retry_' . ($data['msg_id'] ?? '');
                $retryCount = intval(cache($retryKey) ?: 0);
            }
        } catch (\Throwable $e) {
            // 缓存抖动不影响重试判定，按可重试处理
            return true;
        }
        $maxRetry = intval($this->signVerifyRetryMax ?: 3);
        if ($retryCount >= $maxRetry) {
            $this->actionLogSafely([
                'msg_id' => $data['msg_id'] ?? '',
                'machine_id' => $data['machine_id'] ?? '',
                'retry_count' => $retryCount,
                'max_retry' => $maxRetry,
            ], '验签关键消息重试次数达上限，丢弃', 'DataUpload');
            return false;
        }
        if ($retryKey) {
            try {
                cache($retryKey, $retryCount + 1, 300);
            } catch (\Throwable $e) {
                // 缓存失败不阻塞
            }
        }
        return true;
    }

    /**
     * 日志故障不得阻塞 ACK/NACK。
     */
    protected function actionLogSafely($data, $remark = '', $logName = '')
    {
        try {
            actionLog($data, $remark, $logName);
        } catch (\Throwable $e) {
            error_log('MQ action log failed: ' . $e->getMessage());
        }
    }

    /**
     * 异常日志采用失败隔离，避免异常处理自身再次抛错。
     */
    protected function logConsumerExceptionSafely(\Throwable $e)
    {
        $this->actionLogSafely(
            $e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(),
            'tryCatchMessage',
            'DataUpload'
        );
        $this->actionLogSafely($e->getTrace(), 'tryCatchTrace', 'DataUpload');
    }

    /**
     * 消费者——系统
     * @throws \Exception
     */
    public function dataUpload()
    {
        $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
        if (!$param) {
            throw new \RuntimeException("获取不到RabbitMQ【" . env("RabbitMq.config_name") . "】的连接配置参数");
        }
        $amqpDetail = config('rabbit_mq.dataUpload_queue');
        if (!$amqpDetail) {
            throw new \RuntimeException("获取不到终端上传相关配置参数【dataUpload_queue】");
        }
        $connection = MqConnectionFactory::create($param);
        /**
        *创建通道
        */
        $channel = $connection->channel();
        $this->actionLogSafely([
            'consumer_stage' => 'STARTED',
            'queue_name' => $amqpDetail['queue_name'],
            'consumer_tag' => $amqpDetail['consumer_tag'],
            'prefetch_count' => 1,
        ], 'MQ消费者状态', 'DataUploadConsumer');
        /**
        * 设置消费者(Consumer)客户端同时只处理一条队列
        *这样是告诉RabbitMQ，再同一时刻，不要发送超过1条消息给一个消费者(Consumer)，
        *直到它已经处理了上一条消息并且作出了响应。这样，RabbitMQ就会把消息分发给下一个空闲的消费者(Consumer)。
         */
        $channel->basic_qos(0, 1, false);
        /**
         *同样是创建路由和队列，以及绑定路由队列，注意要跟publisher的一致
         *这里其实可以不用，但是为了防止队列没有被创建所以做的容错处理
         */
        $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
        $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
        $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);
        /**
         * queue: 从哪里获取消息的队列
         * consumer_tag: 消费者标识符，用于区分多个客户端
         * no_local: 不接收此使用者发布的消息
         * no_ack: 设置为true，则使用者将使用自动确认模式。详情请参见自动ACK:消息一旦被接收，消费者自动发送ACK
         * 手动ACK:消息接收后，不会发送ACK，需要手动调用
         * exclusive:是否排他，即这个队列只能由一个消费者消费。适用于任务不允许进行并发处理的情况下
         * nowait: 不返回执行结果，但是如果排他开启的话，则必须需要等待结果的，如果两个一起开就会报错
         * callback: :回调逻辑处理函数,PHP回调 array($this，process message') 调用本对象的process message方法
         */
        $channel->basic_consume($amqpDetail['queue_name'], $amqpDetail['consumer_tag'], false, false, false, false, array($this, 'process_message'));
        try {
            // 阻塞队列监听事件
            while (count($channel->callbacks)) {
                $channel->wait();
            }
        } finally {
            $this->actionLogSafely([
                'consumer_stage' => 'STOPPED',
                'queue_name' => $amqpDetail['queue_name'],
                'consumer_tag' => $amqpDetail['consumer_tag'],
            ], 'MQ消费者状态', 'DataUploadConsumer');
            $this->shutdownSafely($channel, $connection);
        }
    }

    /**
     * 消费者——导出
     * @throws \Exception
     */
    public function export_queue()
    {
        $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
        if (!$param) {
            throw new \RuntimeException("获取不到RabbitMQ【" . env("RabbitMq.config_name") . "】的连接配置参数");
        }
        $amqpDetail = config('rabbit_mq.export_queue');
        if (!$amqpDetail) {
            throw new \RuntimeException("获取不到终端上传相关配置参数【export_queue】");
        }
        $connection = MqConnectionFactory::create($param);
        /**
        *创建通道
        */
        $channel = $connection->channel();
        /**
        * 设置消费者(Consumer)客户端同时只处理一条队列
        *这样是告诉RabbitMQ，再同一时刻，不要发送超过1条消息给一个消费者(Consumer)，
        *直到它已经处理了上一条消息并且作出了响应。这样，RabbitMQ就会把消息分发给下一个空闲的消费者(Consumer)。
         */
        $channel->basic_qos(0, 1, false);
        /**
         *同样是创建路由和队列，以及绑定路由队列，注意要跟publisher的一致
         *这里其实可以不用，但是为了防止队列没有被创建所以做的容错处理
         */
        $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
        $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
        $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);
        /**
         * queue: 从哪里获取消息的队列
         * consumer tag: 消费者标识符，用于区分多个客户端
         * no local: 不接收此使用者发布的消息
         * no ack: 设置为true，则使用者将使用自动确认模式。详情请参见自动ACK:消息一旦被接收，消费者自动发送ACK
         * 手动ACK:消息接收后，不会发送ACK，需要手动调用
         * exclusive:是否排他，即这个队列只能由一个消费者消费。适用于任务不允许进行并发处理的情况下
         * nowait: 不返回执行结果，但是如果排他开启的话，则必须需要等待结果的，如果两个一起开就会报错
         * callback: :回调逻辑处理函数,PHP回调 array($this，export_message') 调用本对象的export_message方法
         */
        $channel->basic_consume($amqpDetail['queue_name'], $amqpDetail['consumer_tag'], false, false, false, false, array($this, 'export_message'));
        try {
            // 阻塞队列监听事件
            while (count($channel->callbacks)) {
                $channel->wait();
            }
        } finally {
            $this->shutdownSafely($channel, $connection);
        }
    }

    /**
     * 消息处理（幂等+最大重投限制）
     * @param $message
     */
    public function export_message(AMQPMessage $message)
    {
        $data = [];
        try {
            $data = $message->body;
            $data = json2arr($data);
            if (!is_array($data)) {
                $message->ack();
                return;
            }
            $exportId = intval($data['export_id'] ?? 0);
            $jobType = $data['job_type'] ?? 'export';
            actionLog([
                'job_type' => $jobType,
                'export_id' => $exportId,
                'filename' => $data['filename'] ?? '',
                'row_count' => isset($data['list']) && is_array($data['list']) ? count($data['list']) : 0,
            ], '消息处理摘要', "export_message");

            // 幂等：已完成（status=2）的导出直接 ack 跳过，避免重复重投重复执行
            // 查询失败时按未完成处理，避免 DB 抖动误跳过真正未处理的任务
            if ($exportId) {
                try {
                    $exportLogStatus = Db::name('export_log')->where('export_id', $exportId)->value('status');
                    if (intval($exportLogStatus) === 2) {
                        $message->ack();
                        return;
                    }
                } catch (\Throwable $e) {
                    actionLog($e->getMessage(), '幂等查询export_log失败，继续处理', "export_message");
                }
            }

            // 最大重投次数限制：超过阈值则收走消息并标记失败，避免无限重投循环
            if ($this->exportRedeliverExceeded($message, $exportId)) {
                $message->ack();
                return;
            }

            if ($jobType == 'sale_orders_export') {
                $app = AppFactory::timeTask();
                if (!$app->export->makeSaleOrdersExcel($data)) {
                    throw new \RuntimeException('销售订单导出Excel生成失败');
                }
            } elseif ($jobType == 'multi_sheet_export') {
                $app = AppFactory::timeTask();
                if (!$app->export->makeMultiSheetExcel($data)) {
                    throw new \RuntimeException('多Sheet导出Excel生成失败');
                }
            } elseif ($jobType == 'goods_so_export') {
                $app = AppFactory::timeTask();
                if (!$app->export->makeGoodsSoExcel($data)) {
                    throw new \RuntimeException('商品交易列表导出Excel生成失败');
                }
            } else {
                $app = AppFactory::timeTask();
                if (!$app->export->makeExcel($data)) {
                    throw new \RuntimeException('导出Excel生成失败');
                }
            }
            $message->ack();
        } catch (\Throwable $e) {
            actionLog($e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(),'tryCatchMessage',"export_message");
            actionLog($e->getTrace(), 'tryCatchTrace',"export_message");
            // 失败消息 requeue 重投，由 exportRedeliverExceeded 在超限后收走并标记失败，
            // 避免瞬时故障（DB抖动/文件系统忙）导致导出静默失败。
            $message->nack(false, true);
        }
    }

    /**
     * 判断导出消息重投次数是否超过上限（默认3次），超限后收走消息避免无限重投。
     *
     * @param AMQPMessage $message
     * @param int $exportId
     * @return bool
     */
    protected function exportRedeliverExceeded(AMQPMessage $message, $exportId)
    {
        $maxRedeliver = max(1, intval(config('rabbit_mq.export_max_redeliver') ?: 3));
        $redeliverCount = 0;
        // x-death 位于 application_headers（AMQPTable）内，不在消息顶层 properties
        if ($message->has('application_headers')) {
            try {
                $headers = $message->get('application_headers');
                $native = is_object($headers) && method_exists($headers, 'getNativeData')
                    ? $headers->getNativeData()
                    : (array)$headers;
                if (isset($native['x-death']) && is_array($native['x-death'])) {
                    foreach ($native['x-death'] as $death) {
                        if (is_array($death) && isset($death['count'])) {
                            $redeliverCount += intval($death['count']);
                        }
                    }
                }
            } catch (\Throwable $e) {
                actionLog($e->getMessage(), '读取x-death头失败', "export_message");
            }
        }
        if ($redeliverCount === 0 && $message->isRedelivered()) {
            $redeliverCount = 1;
        }
        if ($redeliverCount >= $maxRedeliver) {
            actionLog([
                'export_id' => $exportId,
                'redeliver_count' => $redeliverCount,
                'max_redeliver' => $maxRedeliver,
            ], '导出消息重投超限，已收走消息', "export_message");
            if ($exportId) {
                AppFactory::timeTask()->export->updateExportLog(['export_id' => $exportId, 'status' => 4]);
            }
            return true;
        }
        return false;
    }

}
