<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 13:52
 */

namespace app\AppFactory\WorkerMan\RabbitMq;

use Bunny\Channel;
use React\Promise\PromiseInterface;
use think\facade\Config;
use Workerman\Connection\ConnectionInterface;
use Workerman\RabbitMQ\Client;
use think\worker\Server;

class Send extends Server
{
    protected $socket = "websocket://127.0.0.1:2345";

    /**
     * @param ConnectionInterface $connection
     * @param $data
     */
    public function onMessage($connection,$data)
    {
        dump($data);
        // webSocket发送过来的消息
        $connection->send("receive success! : " . $data);
        // rabbitMQ配置
        $options = Config::get("rabbit_mq.AMQP");
        $mq = new Client($options);
        $mq = $mq->connect();
        $mq = $mq->then(function (Client $client) {
            return $client->channel();
        });
        $mq = $mq->then(function (Channel $channel) {
            /**
             * 创建队列
             * queue: machine             队列名称
             * passive: false             如果设置true，存在则返回OK，不存在就报错；设置FALSE，存在返回OK，不存在则自动创建
             * durable: true              是否持久化，
             *                                  设置false是存放到内存中，rabbitMQ重启后会丢失
             *                                  设置true则代表是一个持久的队列，服务重启之后也会存在，因为服务会把持久化的queue存放在硬盘上，当服务重启时，会重新加载之前被持久化的Queue
             * exclusive:false            是否排他，指定该选项为true则队列只对当前连接有效，连接断开后自动删除
             * autoDelete:false            是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             */
            return $channel->queueDeclare('machine',false,true,false,false)->then(function () use ($channel) {
                    dump($channel);
                    return $channel;
                });
        });
        $mq = $mq->then(function (Channel $channel) use ($data) {
            echo "发送消息：" . $data . "\n";

            /**
             * 发送消息
             * body   发送的数据
             * headers  数据头，建议['content_type' => "text_plain"]，这样消费端是springboot注解接收直接是字符串类型
             * exchange   交换器名称
             * routingKey 路由Key
             * mandatory
             * immediate
             * @return bool|PromiseInterface|int
             */
            return $channel->publish($data,['content_type' => "text_plain"],'','machine')->then(function () use ($channel) {
                return $channel;
            });
        });
        $mq = $mq->then(function (Channel $channel) {
            $client = $channel->getClient();
            return $channel->close()->then(function () use ($client) {
                return $client;
            });
        });
        $mq = $mq->then(function (Client $client) {
            $client->disconnect();
        });
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {

    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {

    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection,$code,$msg)
    {
        echo "error $code $msg \n";
    }

    /**
     * 每个进程启动触发
     * @param $worker
     */
    public function onWorkerStart($worker)
    {

    }
}