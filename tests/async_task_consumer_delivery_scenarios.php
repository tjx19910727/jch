<?php

namespace PhpAmqpLib\Channel {
    class AMQPChannel {}
}

namespace PhpAmqpLib\Connection {
    class AMQPStreamConnection {}
}

namespace PhpAmqpLib\Connection\Heartbeat {
    class PCNTLHeartbeatSender {}
}

namespace PhpAmqpLib\Message {
    class AMQPMessage
    {
        public $body;
        public $ackCount = 0;
        public $throwOnAck = false;

        public function __construct($body, $throwOnAck = false)
        {
            $this->body = $body;
            $this->throwOnAck = $throwOnAck;
        }

        public function ack($multiple = false)
        {
            $this->ackCount++;
            if ($this->throwOnAck) throw new \RuntimeException('simulated ack failure');
        }
    }
}

namespace think\facade {
    class Cache
    {
        public static $values = [];

        public static function get($key)
        {
            return self::$values[$key] ?? null;
        }

        public static function set($key, $value, $ttl = null)
        {
            self::$values[$key] = $value;
            return true;
        }
    }

    class Log {}
}

namespace app\AppFactory\RabbitMq\AsyncTask {
    class AsyncTaskHandlerFactory
    {
        public static $handleCount = 0;

        public static function make($taskType)
        {
            return new class {
                public function handle($payload, $task)
                {
                    AsyncTaskHandlerFactory::$handleCount++;
                    return ['ok' => true];
                }
            };
        }
    }
}

namespace {
    function json2arr($body)
    {
        return json_decode($body, true);
    }

    function actionLog($data, $title, $channel)
    {
        return true;
    }

    require dirname(__DIR__) . '/app/AppFactory/RabbitMq/AsyncTaskConsumer.php';

    $consumer = new \app\AppFactory\RabbitMq\AsyncTaskConsumer();
    $body = json_encode([
        'task_id' => 'task_ack_lost',
        'task_type' => 'scenario',
        'payload' => [],
    ]);

    $firstDelivery = new \PhpAmqpLib\Message\AMQPMessage($body, true);
    $ackFailed = false;
    try {
        $consumer->process_message($firstDelivery);
    } catch (\RuntimeException $e) {
        $ackFailed = $e->getMessage() === 'simulated ack failure';
    }

    if (!$ackFailed || $firstDelivery->ackCount !== 1) {
        fwrite(STDERR, '[FAIL] A failed acknowledgement must be attempted exactly once' . PHP_EOL);
        exit(1);
    }

    $secondDelivery = new \PhpAmqpLib\Message\AMQPMessage($body);
    $consumer->process_message($secondDelivery);

    if (\app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerFactory::$handleCount !== 1) {
        fwrite(STDERR, '[FAIL] A redelivery after lost ack must not execute completed business work again' . PHP_EOL);
        exit(1);
    }

    if ($secondDelivery->ackCount !== 1) {
        fwrite(STDERR, '[FAIL] A duplicate delivery must be acknowledged exactly once' . PHP_EOL);
        exit(1);
    }

    echo '[PASS] Async task lost-ack redelivery scenario passed' . PHP_EOL;
}
