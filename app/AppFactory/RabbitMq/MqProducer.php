<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 16:04
 */

namespace app\AppFactory\RabbitMq;


use app\AppFactory\AppFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MqProducer
{
    /**
     * 生产者
     * 下发设备发送数据
     * @param $data
     * @param $machine_id
     * @return string
     */
    public static function dataSend($data, $machine_id)
    {
        try {
            $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
            $amqpDetail = config('rabbit_mq.dataSend_queue');
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
            $connection->isConnected() or die("Cannot connect to the broker \n");

            $channel = $connection->channel();
            /**
             * 启用事务
             */
//            $channel->tx_select();
            /**
             * 启用确认机制
             */
            $channel->confirm_select();
            /**
             * 创建队列(Queue)
             * name: hello         // 队列名称
             * passive: false       // 如果设置true存在则返回OK，否则就报错。设置false存在返回0K，不存在则自动创建
             * durable: true         // 是否持久化，设置false是存放到内存中RabbitMQ重启后会丢失，
             *                       // 设置true则代表是一个持久的队列，服务重启之后也会存在，因为服务会把持久化的Queue存放在硬盘上，当服务重启时，会重新加载之前被持久化的Queue
             * exclusive: false      //是否排他，指定该选项为true则队列只对当前连接有效，连接断开后自动删除
             * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             */
            $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
            /**
             * 创建交换机(Exchange)
             * name: vckai_exchange   // 交换机名称
             * type: direct   // 交换机类型，分别为direct/fanout/topic，参考另外文章的Exchange Type说明。
             * passive: false   //如果设置true存在则返回0K，否则就报错。设置false存在返回OK，不存在则自动创建
             * durable: false   //是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失*
             * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             */
            $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
            /**
             * 绑定队列和交换机
             * @param string $queue 队列名称
             * @param string $exchange 交换器名称
             * @param string $routing_key 路由key
             * @param bool $nowait
             * @param array $arguments
             * @param int|null $ticket
             * @throws  \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
             * @return mixed|null
             */
            $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);
            /**
             * $messageBody:消息体
             * content_type: 消息的类型 可以不指定
             * delivery_mode:消息持久化最关键的参数
             * AMQPMessage::DELIVERY_MODE_NON_PERSISTENT = 1; 不持久化
             * AMQPMessage::DELIVERY_MODE_PERSISTENT = 2: 持久化
             *///将要发送数据变为json字符串
            $messageBody = json_encode($data);
            /**
             * 创建AMQP消息类型
             * $messageBody:消息体
             * delivery_mode 消息是否持久化
             * AMQPMessage::DELIVERY_MODE_NON_PERSISTENT = 1; 不持久化AMOPMessage: :DELIVERY_MODE_PERSISTENT = 2: 持久化
             */
            $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

            $channel->set_ack_handler(function (AMQPMessage $message){
                $message = json2arr($message->getBody());
                $config = [
                    "machine_id" => $message['machine_id'],
//                    "key" => env("app.md5Key")
                ];
                actionLog($message,'异步发布者确认信息');
                $app = AppFactory::machine($config);
                $app->sendMq->confirmSend($message,2);
            });
            $channel->set_nack_handler(function (AMQPMessage $message){
                $message = json2arr($message->getBody());
                $config = [
                    "machine_id" => $message['machine_id'],
//                    "key" => env("app.md5Key")
                ];
                actionLog($message,'异步丢失消息回调数据');
                $app = AppFactory::machine($config);
                $app->sendMq->confirmSend($message,3);
            });
            /**
             * 发送消息
             * mSg// AMQP消息内容
             * exchange //交换机名称
             * routing key//路由键名称
             *
             */
            $channel->basic_publish($message, $amqpDetail['exchange_name'], $amqpDetail['route_key']);
            $channel->wait_for_pending_acks(1);
//            $channel->tx_commit();
            $channel->close();
            $connection->close();
            return true;
        } catch (\Exception $e) {
            actionException($e,1);
            return returnTryCatch($e->getMessage());
        }
    }

    /**
     * 生产者
     * 测试设备返回数据
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public static function dataUpload($data)
    {
        $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
        $amqpDetail = config('rabbit_mq.dataUpload_queue');

//        if (strpos($amqpDetail['route_key'], ".")) {
//            $temp = explode(".", $amqpDetail);
//            foreach ($temp as $key => $value) {
//                $value = $value . "/" . $machine_id;
//                $temp[$key] = $value;
//            }
//            $amqpDetail['route_key'] = implode(".", $temp);
//        } else {
//            $amqpDetail['route_key'] .= "/" . $machine_id;
//        }
//        $amqpDetail['queue_name'] = $amqpDetail['queue_name'] . "_" . $machine_id;

        $connection = new AMQPStreamConnection(
            $param['host'],
            $param['port'],
            $param['login'],
            $param['password'],
            $param['vhost']
        );
        $channel = $connection->channel();
        /**
         * 创建队列(Queue)
         * name: hello         // 队列名称
         * passive: false       // 如果设置true存在则返回OK，否则就报错。设置false存在返回0K，不存在则自动创建
         * durable: true         // 是否持久化，设置false是存放到内存中RabbitMQ重启后会丢失，
         *                       // 设置true则代表是一个持久的队列，服务重启之后也会存在，因为服务会把持久化的Queue存放在硬盘上，当服务重启时，会重新加载之前被持久化的Queue
         * exclusive: false      //是否排他，指定该选项为true则队列只对当前连接有效，连接断开后自动删除
         * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
         */
        $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
        /**
         * 创建交换机(Exchange)
         * name: vckai_exchange   // 交换机名称
         * type: direct   // 交换机类型，分别为direct/fanout/topic，参考另外文章的Exchange Type说明。
         * passive: false   //如果设置true存在则返回0K，否则就报错。设置false存在返回OK，不存在则自动创建
         * durable: false   //是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失*
         * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
         */
        $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
        /**
         * 绑定队列和交换机
         * @param string $queue 队列名称
         * @param string $exchange 交换器名称
         * @param string $routing_key 路由key
         * @param bool $nowait
         * @param array $arguments
         * @param int|null $ticket
         * @throws  \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
         * @return mixed|null
         */
        $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);

        /**
         * $messageBody:消息体
         * content_type: 消息的类型 可以不指定
         * delivery_mode:消息持久化最关键的参数
         * AMQPMessage::DELIVERY_MODE_NON_PERSISTENT = 1; 不持久化
         * AMQPMessage::DELIVERY_MODE_PERSISTENT = 2: 持久化
         */
        //将要发送数据变为json字符串
        $messageBody = json_encode($data);
        /**
         * 创建AMQP消息类型
         * $messageBody:消息体
         * delivery_mode 消息是否持久化
         * AMQPMessage::DELIVERY_MODE_NON_PERSISTENT = 1; 不持久化AMOPMessage: :DELIVERY_MODE_PERSISTENT = 2: 持久化
         */
        $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        /**
        发送消息
        * mSg// AMQP消息内容
        * exchange //交换机名称
        * routing key//路由键名称
         *
         */
        $channel->basic_publish($message, $amqpDetail['exchange_name'], $amqpDetail['route_key']);

        $channel->close();
        $connection->close();
        return "OK";
    }


    /**
     * 生产者
     * 导出数据放入队列
     * @param array $data
     * @return string
     */
    public static function export($data)
    {
        try {
            $param = config('rabbit_mq.' . env("RabbitMq.config_name"));
            $amqpDetail = config('rabbit_mq.export_queue');

            $connection = new AMQPStreamConnection(
                $param['host'],
                $param['port'],
                $param['login'],
                $param['password'],
                $param['vhost']
            );
            $channel = $connection->channel();
            /**
             * 创建队列(Queue)
             * name: hello         // 队列名称
             * passive: false       // 如果设置true存在则返回OK，否则就报错。设置false存在返回0K，不存在则自动创建
             * durable: true         // 是否持久化，设置false是存放到内存中RabbitMQ重启后会丢失，
             *                       // 设置true则代表是一个持久的队列，服务重启之后也会存在，因为服务会把持久化的Queue存放在硬盘上，当服务重启时，会重新加载之前被持久化的Queue
             * exclusive: false      //是否排他，指定该选项为true则队列只对当前连接有效，连接断开后自动删除
             * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             */
            $channel->queue_declare($amqpDetail['queue_name'], false, true, false, false);
            /**
             * 创建交换机(Exchange)
             * name: vckai_exchange   // 交换机名称
             * type: direct   // 交换机类型，分别为direct/fanout/topic，参考另外文章的Exchange Type说明。
             * passive: false   //如果设置true存在则返回0K，否则就报错。设置false存在返回OK，不存在则自动创建
             * durable: false   //是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失*
             * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             */
            $channel->exchange_declare($amqpDetail['exchange_name'], $amqpDetail['exchange_type'], false, true, false);
            /**
             * 绑定队列和交换机
             * @param string $queue 队列名称
             * @param string $exchange 交换器名称
             * @param string $routing_key 路由key
             * @param bool $nowait
             * @param array $arguments
             * @param int|null $ticket
             * @throws  \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
             * @return mixed|null
             */
            $channel->queue_bind($amqpDetail['queue_name'], $amqpDetail['exchange_name'], $amqpDetail['route_key']);

            /**
             * $messageBody:消息体
             * content_type: 消息的类型 可以不指定
             * delivery_mode:消息持久化最关键的参数
             * AMQPMessage::DELIVERY_MODE_NON_PERSISTENT = 1; 不持久化
             * AMQPMessage::DELIVERY_MODE_PERSISTENT = 2: 持久化
             */
            //将要发送数据变为json字符串
            $messageBody = json_encode($data);
            /**
             * 创建AMQP消息类型
             * $messageBody:消息体
             * delivery_mode 消息是否持久化
             * AMQPMessage::DELIVERY_MODE_NON_PERSISTENT = 1; 不持久化AMOPMessage: :DELIVERY_MODE_PERSISTENT = 2: 持久化
             */
            $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
            /**
             * 发送消息
             * mSg// AMQP消息内容
             * exchange //交换机名称
             * routing key//路由键名称
             *
             */
            $channel->basic_publish($message, $amqpDetail['exchange_name'], $amqpDetail['route_key']);

            $channel->close();
            $connection->close();
            return "OK";
        } catch (\Exception $e) {
            actionException($e,1);
            return $e->getMessage();
        }
    }
}