<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$mediaFields = ['pic_out_goods_box', 'video_out_goods_box', 'video_refund_goods'];
$emptyMediaPayload = [
    'pic_out_goods_box' => '',
    'video_out_goods_box' => '',
    'video_refund_goods' => '',
];
$validator = new \app\machine\validate\VReceive();
$emptyMediaValid = $validator->only($mediaFields)->check($emptyMediaPayload);
$validator = new \app\machine\validate\VReceive();
$missingMediaValid = $validator->only($mediaFields)->check([]);
$overlongMediaRejected = false;
try {
    $validator = new \app\machine\validate\VReceive();
    $validator->only($mediaFields)
        ->check(['pic_out_goods_box' => str_repeat('a', 1001)]);
} catch (\Throwable $e) {
    $overlongMediaRejected = true;
}

$checks = [
    'refund log model loads' => class_exists(\app\AppFactory\Kernel\Model\Machine\MachineRefundGoodsLogModel::class),
    'refund log trait loads' => trait_exists(\app\AppFactory\Kernel\Traits\Machine\MachineRefundGoodsLogTrait::class),
    'receive api loads' => class_exists(\app\AppFactory\Machine\Receive\ApiClient::class),
    'receive api exposes submit method' => method_exists(\app\AppFactory\Machine\Receive\ApiClient::class, 'submitRefundGoodsLog'),
    'receive api exposes log insert method' => method_exists(\app\AppFactory\Machine\Receive\ApiClient::class, 'addMachineRefundGoodsLog'),
    'special code uses class property' => strpos(
        file_get_contents(dirname(__DIR__) . '/app/AppFactory/Machine/Receive/ApiClient.php'),
        "protected \$refundGoodsSpecialCode = '0000';"
    ) !== false,
    'submit method does not read special code config' => strpos(
        file_get_contents(dirname(__DIR__) . '/app/AppFactory/Machine/Receive/ApiClient.php'),
        "config('refund_goods.special_code')"
    ) === false,
    'refund goods config removed' => !is_file(dirname(__DIR__) . '/config/refund_goods.php'),
    'empty media fields pass validation' => $emptyMediaValid,
    'missing media fields pass validation' => $missingMediaValid,
    'overlong media field is rejected' => $overlongMediaRejected,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) {
        $failed[] = $name;
    }
}

exit($failed ? 1 : 0);
