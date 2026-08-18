<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 15:14
 */

return [
    // RabbitMQ连接可靠性配置：读写超时需大于心跳周期的2倍
    'connection_timeout' => 3,
    'read_write_timeout' => 65,
    'heartbeat' => 30,
    'keepalive' => true,
    'consumer_reconnect_initial_delay' => 1,
    'consumer_reconnect_max_delay' => 30,

    // 后台下发到设备的 MQ 消息过期时间，单位：毫秒
    'data_send_expiration_ms' => 180 * 1000,

    // 设备 signKey 最小重发冷却间隔，单位：秒。
    // 设备在冷却期内重复请求认证时，会复用已有signKey强制重发MQ（限流 signKeyForceResendThreshold 次）。
    // 调小（如10~20秒）可加速设备认证恢复，避免用户付款后长时间等待出货；但不能低于10秒。
    // 建议值：20（默认值，已在 ReceiveBaseClient 中），若设备认证间题频繁可下探到10。
    'sign_key_resend_cooldown' => 20,

    // 设备 HTTP 请求 timestamp 允许落后服务器的秒数
    'machine_receive_timestamp_tolerance' => 180,

    // 设备签名密钥有效期提示，单位：秒。设备端应按收到后的经过时间判断，不依赖本机绝对时间。
    'machine_sign_key_expires_in' => 3600,

    // "AMQP" => [
    //     "host" => "127.0.0.1",      // rabbitMQ IP
    //     'port' => 5673,             // rabbitMQ 端口
    //     'user' => 'dkm',            // rabbitMQ 账号
    //     'password' => '123456'      // rabbitMQ 密码
    // ],

    // // 连接本地RabbitMQ
    // 'AMQP_local' => [
    //     'host' => '127.0.0.1',//连接rabbitmq,此为安装rabbitmq服务器port'=>'5672'
    //     'port' => 5672,
    //     'login' => 'dkm',
    //     'password' => 'dkm123456',
    //     'vhost' => '/',
    // ],

    // 服务器连接配置
    'AMQP_online' => [
        'host' => '172.16.0.81',//  连接rabbitmq,   此为安装rabbitmq服务器port'=>'5672'81
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

    # 异步任务队列
    'async_task_queue' => [
        'exchange_name' => 'async_task_exchange',
        'exchange_type' => 'topic',#主题
        'queue_name' => 'async_task_queue',
        'route_key' => 'async_task',
        'consumer_tag' => 'async_task',
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
