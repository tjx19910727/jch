<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 13:52
 */

namespace app\AppFactory\WorkerMan\RabbitMq;

use Bunny\Channel;
use Bunny\Message;
use React\Promise\PromiseInterface;
use think\facade\Config;
use Workerman\Connection\ConnectionInterface;
use Workerman\RabbitMQ\Client;
use think\worker\Server;

class Receive extends Server
{
    protected $socket = "tcp://127.0.0.1:2346";

    /**
     * @param ConnectionInterface $connection
     * @param $data
     */
    public function onMessage($connection,$data)
    {
        dump($data);
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
        // rabbitMQ配置
        $options = Config::get("rabbit_mq.AMQP");;
        (new Client($options))->connect()->then(function (Client $client) {
            return $client->channel();
        })->then(function (Channel $channel) {
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
            return $channel->queueDeclare('machine',false,true,false,false)
                ->then(function () use ($channel) {
                    return $channel;
                });
        })->then(function (Channel $channel) {
            echo "[*] Waiting for messages. To exit press CTRL+C" , "\n";
            $channel->consume(
                function (Message $message, Channel $channel, Client $client) {
                    dump($message);
                    echo "接收消息内容：", $message->content,"\n";
                },'machine','',false,true
            );
        });
    }
}