<?php

$root = dirname(__DIR__);
$config = file_get_contents($root . '/config/rabbit_mq.php');
$mqProducer = file_get_contents($root . '/app/AppFactory/RabbitMq/MqProducer.php');
$receiveBase = file_get_contents($root . '/app/AppFactory/Machine/Receive/ReceiveBaseClient.php');
$receiveController = file_get_contents($root . '/app/machine/controller/Receive.php');
$vReceive = file_get_contents($root . '/app/machine/validate/VReceive.php');
$vRobot = file_get_contents($root . '/app/machine/validate/VRobot.php');
$sendBase = file_get_contents($root . '/app/AppFactory/Machine/Send/SendBaseClient.php');

$checks = [
    '通信配置项保持文档字段名' =>
        strpos($config, "'data_send_expiration_ms' => 180 * 1000") !== false
        && strpos($config, "'machine_receive_timestamp_tolerance' => 180") !== false
        && strpos($config, "'machine_sign_key_expires_in' => 3600") !== false,
    'MQ下发消息使用TTL过期配置' =>
        strpos($mqProducer, "config('rabbit_mq.data_send_expiration_ms') ?: (180 * 1000)") !== false
        && strpos($mqProducer, "\$messageProps['expiration'] = \$expiration") !== false,
    'signKey下发包含服务端时间和有效期字段' =>
        strpos($receiveBase, "config('rabbit_mq.machine_sign_key_expires_in') ?: 3600") !== false
        && strpos($receiveBase, "config('rabbit_mq.machine_receive_timestamp_tolerance') ?: 180") !== false
        && strpos($receiveBase, '"server_time" => $now') !== false
        && strpos($receiveBase, '"expires_in" => $expiresIn') !== false
        && strpos($receiveBase, '"expires_at" => $now + $expiresIn') !== false
        && strpos($receiveBase, '"timestamp_tolerance" => $timestampTolerance') !== false,
    'timestamp超时响应包含服务端校准字段' =>
        strpos($receiveController, '$message == "VReceive.timestamp_checkTimestamp_overdue"') !== false
        && strpos($receiveController, "'server_time' => \$serverTime") !== false
        && strpos($receiveController, "'request_timestamp' => \$requestTimestamp") !== false
        && strpos($receiveController, "'server_time_offset' => \$requestTimestamp ? \$serverTime - \$requestTimestamp : 0") !== false
        && strpos($receiveController, "'timestamp_tolerance' => \$timestampTolerance") !== false,
    '设备入口timestamp校验读取配置' =>
        substr_count($vReceive . $vRobot, "config('rabbit_mq.machine_receive_timestamp_tolerance') ?: 180") >= 2
        && strpos($vReceive, 'VReceive.timestamp_checkTimestamp_overdue') !== false
        && strpos($vRobot, 'VReceive.timestamp_checkTimestamp_overdue') !== false,
    '普通MQ下发未新增服务端时间顶层字段' =>
        strpos($sendBase, '"server_time"') === false
        && strpos($sendBase, '"expires_in"') === false
        && strpos($sendBase, '"timestamp_tolerance"') === false,
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "[FAIL] {$name}" . PHP_EOL);
        exit(1);
    }
    echo "[OK] {$name}" . PHP_EOL;
}

echo "[PASS] 设备MQ过期与时间偏差后台改造守卫通过" . PHP_EOL;
