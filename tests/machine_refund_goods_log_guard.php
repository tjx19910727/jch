<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$checks = [
    'refund log model loads' => class_exists(\app\AppFactory\Kernel\Model\Machine\MachineRefundGoodsLogModel::class),
    'refund log trait loads' => trait_exists(\app\AppFactory\Kernel\Traits\Machine\MachineRefundGoodsLogTrait::class),
    'receive api loads' => class_exists(\app\AppFactory\Machine\Receive\ApiClient::class),
    'receive api exposes submit method' => method_exists(\app\AppFactory\Machine\Receive\ApiClient::class, 'submitRefundGoodsLog'),
    'receive api exposes log insert method' => method_exists(\app\AppFactory\Machine\Receive\ApiClient::class, 'addMachineRefundGoodsLog'),
    'refund goods config exists' => is_file(dirname(__DIR__) . '/config/refund_goods.php'),
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) {
        $failed[] = $name;
    }
}

exit($failed ? 1 : 0);
