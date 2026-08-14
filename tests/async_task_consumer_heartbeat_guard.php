<?php

$root = dirname(__DIR__);
$consumer = file_get_contents($root . '/app/AppFactory/RabbitMq/AsyncTaskConsumer.php');
$failures = [];

function checkAsyncTaskConsumerGuard($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

checkAsyncTaskConsumerGuard(
    strpos($consumer, 'new PCNTLHeartbeatSender($connection)') !== false
        && strpos($consumer, '$heartbeatSender->register()') !== false
        && strpos($consumer, '$heartbeatSender->unregister()') !== false
        && strpos($consumer, "constant('AMQP_WITHOUT_SIGNALS')") !== false
        && strpos($consumer, '!AMQP_WITHOUT_SIGNALS') === false,
    'The async consumer must keep RabbitMQ heartbeats active during long-running handlers',
    $failures
);

checkAsyncTaskConsumerGuard(
    substr_count($consumer, '$message->ack();') === 1
        && strpos($consumer, '$this->logProcessException($e);') < strpos($consumer, '$message->ack();'),
    'Each delivery must have exactly one acknowledgement call outside the business catch block',
    $failures
);

checkAsyncTaskConsumerGuard(
    strpos($consumer, '$this->isTaskCompleted($taskId)') !== false
        && strpos($consumer, '$this->markTaskCompleted($taskId)') !== false
        && strpos($consumer, "Cache::set(\$this->completedTaskCacheKey(\$taskId), 1, 7 * 24 * 3600)") !== false,
    'Completed task ids must prevent business work from running again after a lost acknowledgement',
    $failures
);

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}" . PHP_EOL);
    exit(1);
}

echo "[PASS] Async task heartbeat, single-ack and deduplication guards passed" . PHP_EOL;
