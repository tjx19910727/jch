<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 16:05
 */

namespace app\AppFactory\RabbitMq;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Log;

class MachineConsumer
{

    /**
     *  消费端 消费端需要保持运行状态实现方式
     *  1 Linux上写定时任务每5分钟运行下该脚本，保证访问服务器的IP比较平缓，不至于旗境
     *  2 nohup php index.php index/Message_Consume/start & 用nohup命令后台运行该脚本
     *  3
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
        try {
            //休眠两秒
            //sleep(2) ;
            $data = $message->body;
            $data = json2arr($data);
            dump($data);
            //手动发送ack
            $message->ack($message->getDeliveryTag());

        } catch (\Exception $e) {
            actionException($e,1);
        }
    }

    /**
     * 消费者——设备
     * @throws \Exception
     */
    public function consumer()
    {
        $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
//        $amqpDetail = config('rabbit_mq.dataUpload_queue');

        $amqpDetail = config('rabbit_mq.test_dataSend_queue');
        $machine_id = "test0001";
        if (strpos($amqpDetail['route_key'], ".")) {
            $temp = explode(".", $amqpDetail);
            foreach ($temp as $key => $value) {
                $value = $value . "/" . $machine_id;
                $temp[$key] = $value;
            }
            $amqpDetail['route_key'] = implode(".", $temp);
        } else {
            $amqpDetail['route_key'] .= "/" . $machine_id;
        }
        $amqpDetail['queue_name'] = $amqpDetail['queue_name'] . "_" . $machine_id;

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
         * no_ack: 设置为true，则使用者将使用自动确认模式。
         * 详情请参见
         *      自动ACK:消息一旦被接收，消费者自动发送ACK
         *      手动ACK:消息接收后，不会发送ACK，需要手动调用
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
}