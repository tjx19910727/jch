<?php

namespace app\AppFactory\RabbitMq;

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * 统一创建带超时、Keepalive 和心跳的 RabbitMQ 连接。
 */
class MqConnectionFactory
{
    /**
     * @param array $param
     * @return AMQPStreamConnection
     */
    public static function create($param)
    {
        if (!is_array($param) || empty($param['host']) || empty($param['port'])
            || empty($param['login']) || !isset($param['password']) || !isset($param['vhost'])) {
            throw new \InvalidArgumentException('RabbitMQ configuration is incomplete');
        }

        $connectionTimeout = floatval(config('rabbit_mq.connection_timeout') ?: 3);
        $readWriteTimeout = floatval(config('rabbit_mq.read_write_timeout') ?: 65);
        $heartbeat = intval(config('rabbit_mq.heartbeat') ?: 30);
        $keepalive = config('rabbit_mq.keepalive');
        $keepalive = $keepalive === null ? true : (bool)$keepalive;

        if ($connectionTimeout <= 0) $connectionTimeout = 3;
        if ($heartbeat <= 0) $heartbeat = 30;
        if ($readWriteTimeout <= $heartbeat * 2) $readWriteTimeout = $heartbeat * 2 + 5;

        return new AMQPStreamConnection(
            $param['host'],
            $param['port'],
            $param['login'],
            $param['password'],
            $param['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $connectionTimeout,
            $readWriteTimeout,
            null,
            $keepalive,
            $heartbeat
        );
    }
}
