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
        $channel->close();
        $connection->close();
        Log::write("closed", 3);
    }

    /**
     * 消息处理
     * @param $message
     */
    public function process_message(AMQPMessage $message)
    {
        //手动发送ack
        $message->ack($message->getDeliveryTag());
        try {
            $data = $message->body;
            $data = json2arr($data);
            if (isset($data['data'])) {
                $actionData = json2arr($data['data']);
                if (isset($actionData['msgType']) && $actionData['msgType'] != "heartbeat")
                    actionLog($data, '消息处理', "DataUpload");
            }
            $config = [
                "machine_id" => $data['machine_id'],
//                "key" => cache($data['machine_id'] . ".signKey"),
                "data" => $data,
                "mac" => $data['mac'] ?? "",
            ];
            $app = AppFactory::machine($config);
            $result = $app->mq->onMessage();
            if (isset($data['data'])) {
                $actionData = json2arr($data['data']);
                if (isset($actionData['msgType']) && $actionData['msgType'] != "heartbeat")
                    actionLog($result, '处理结果','DataUpload');
            }

            $updateResult = $this->updateMachineMqRecord(['status' => 2,'msg_id' => $data['msg_id']],['msg_id' => $data['msg_id']]);
            actionLog($updateResult,'修改MQ记录成功结果','DataUpload');
        } catch (\Exception $e) {
            actionLog($e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(),'tryCatchMessage',"DataUpload");
            actionLog($e->getTrace(), 'tryCatchTrace',"DataUpload");
            $updateResult = $this->updateMachineMqRecord(['status' => 3,'msg_id' => $data['msg_id']],['msg_id' => $data['msg_id']]);
            actionLog($updateResult,'修改MQ记录失败结果','DataUpload');
        }
    }

    /**
     * 消费者——系统
     * @throws \Exception
     */
    public function dataUpload()
    {
        $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
        if (!$param) {
            die("获取不到RabbitMQ【" . env("RabbitMq.config_name") . "】的连接配置参数 \n");
        }
        $amqpDetail = config('rabbit_mq.dataUpload_queue');
        if (!$amqpDetail) {
            die("获取不到终端上传相关配置参数【dataUpload_queue】 \n");
        }
        $connection = new AMQPStreamConnection(
            $param['host'],
            $param['port'],
            $param['login'],
            $param['password'],
            $param['vhost']
        );
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
         * consumer_tag: 消费者标识符，用于区分多个客户端
         * no_local: 不接收此使用者发布的消息
         * no_ack: 设置为true，则使用者将使用自动确认模式。详情请参见自动ACK:消息一旦被接收，消费者自动发送ACK
         * 手动ACK:消息接收后，不会发送ACK，需要手动调用
         * exclusive:是否排他，即这个队列只能由一个消费者消费。适用于任务不允许进行并发处理的情况下
         * nowait: 不返回执行结果，但是如果排他开启的话，则必须需要等待结果的，如果两个一起开就会报错
         * callback: :回调逻辑处理函数,PHP回调 array($this，process message') 调用本对象的process message方法
         */
        $channel->basic_consume($amqpDetail['queue_name'], $amqpDetail['consumer_tag'], false, false, false, false, array($this, 'process_message'));
        register_shutdown_function(array($this, 'shutdown'), $channel, $connection);
        // 阻塞队列监听事件
        while (count($channel->callbacks)) {
            $channel->wait();
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
            die("获取不到RabbitMQ【" . env("RabbitMq.config_name") . "】的连接配置参数 \n");
        }
        $amqpDetail = config('rabbit_mq.export_queue');
        if (!$amqpDetail) {
            die("获取不到终端上传相关配置参数【export_queue】 \n");
        }
        $connection = new AMQPStreamConnection(
            $param['host'],
            $param['port'],
            $param['login'],
            $param['password'],
            $param['vhost']
        );
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
        register_shutdown_function(array($this, 'shutdown'), $channel, $connection);
        // 阻塞队列监听事件
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * 消息处理
     * @param $message
     */
    public function export_message(AMQPMessage $message)
    {
        $data = [];
        try {
            $data = $message->body;
            $data = json2arr($data);
            $jobType = $data['job_type'] ?? 'export';
            actionLog([
                'job_type' => $jobType,
                'export_id' => $data['export_id'] ?? 0,
                'filename' => $data['filename'] ?? '',
                'row_count' => isset($data['list']) && is_array($data['list']) ? count($data['list']) : 0,
            ], '消息处理摘要', "export_message");

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
            } else {
                $app = AppFactory::timeTask();
                if (!$app->export->makeExcel($data)) {
                    throw new \RuntimeException('导出Excel生成失败');
                }
            }
            $message->ack($message->getDeliveryTag());
        } catch (\Throwable $e) {
            actionLog($e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(),'tryCatchMessage',"export_message");
            actionLog($e->getTrace(), 'tryCatchTrace',"export_message");
            $exportId = is_array($data) ? intval($data['export_id'] ?? 0) : 0;
            if ($exportId) {
                AppFactory::timeTask()->export->updateExportLog(['export_id' => $exportId, 'status' => 4]);
            }
            $message->ack($message->getDeliveryTag());
        }
    }
}
