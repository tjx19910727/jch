<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 15:14
 */

return [
    "AMQP" => [
        "host" => "127.0.0.1",      // rabbitMQ IP
        'port' => 5673,             // rabbitMQ 端口
        'user' => 'dkm',            // rabbitMQ 账号
        'password' => '123456'      // rabbitMQ 密码
    ],

    // 连接本地RabbitMQ
    'AMQP_local' => [
        'host' => '127.0.0.1',//连接rabbitmq,此为安装rabbitmq服务器port'=>'5672'
        'port' => 5672,
        'login' => 'dkm',
        'password' => 'dkm123456',
        'vhost' => '/',
    ],

    // 服务器连接配置
    'AMQP_online' => [
        'host' => '172.19.0.195',//  连接rabbitmq,   此为安装rabbitmq服务器port'=>'5672'
        'port' => 5672,
        'login' => 'kiosk',
        'password' => 'Kiosk&2019',
        'vhost' => 'kiosk',
    ],

    # 设备上报数据队列
    'dataUpload_queue' => [
        'exchange_name' => 'dataUpload_exchange',
        'exchange_type' => 'topic',#主题
        'queue_name' => 'dataUpload_queue',
        'route_key' => 'dataUpload',
        'consumer_tag' => 'dataUpload',
    ],

    # 导出Excel队列
    'export_queue' => [
        'exchange_name' => 'export_exchange',
        'exchange_type' => 'topic',#主题
        'queue_name' => 'export_queue',
        'route_key' => 'export',
        'consumer_tag' => 'export',
    ],

    # 发送设备信息队列
    'dataSend_queue' => [
        'exchange_name' => 'dataSend_exchange',
        'exchange_type' => 'topic',#主题
        'queue_name' => 'dataSend_queue',
        'route_key' => "dataSend",
        'consumer_tag' => 'dataSend'
    ],

    // 测试消费系统发送的数据
    'test_dataSend_queue' => [
        'exchange_name' => 'dataSend_exchange',
        'exchange_type' => 'topic',#主题
        'queue_name' => 'dataSend_queue',
        'route_key' => "dataSend",
        'consumer_tag' => 'dataSend'
    ],

];