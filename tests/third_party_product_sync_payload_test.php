<?php

require_once dirname(__DIR__) . '/app/AppFactory/Kernel/Service/Api/ThirdPartyProductSyncPayloadBuilder.php';

use app\AppFactory\Kernel\Service\Api\ThirdPartyProductSyncPayloadBuilder;

$builder = new ThirdPartyProductSyncPayloadBuilder('test-secret');
$goodsPayload = $builder->buildSyncGoods(
    1001,
    ['g_name' => '矿泉水', 'retail_price' => '2.50', 'status' => 1],
    'upsert'
);
$deletePayload = $builder->buildSyncGoods(
    1001,
    [],
    'delete'
);
$machinePayload = $builder->buildMachineProduct(
    [
        'mc_id' => 7229,
        'channel_code' => 'A01',
        'product_id' => 1001,
        'sku' => 'SKU001',
        'bar_code' => 'BAR001',
        'g_name' => '矿泉水',
        'quantity' => 8,
        'sale_price' => '2.50',
        'market_price' => '3.00',
        'cost_price' => '1.00',
        'status' => 1,
    ],
    'M10001'
);
$machinePayloads = $builder->buildMachineProducts('M10001', [
    ['channel_code' => 'A01', 'product_id' => 1001, 'quantity' => 8],
    ['channel_code' => 'A02', 'product_id' => 1002, 'quantity' => 5],
    ['channel_code' => 'A03', 'product_id' => 0, 'quantity' => 0],
]);

$checks = [
    'syncGoods 为扁平结构' => !isset($goodsPayload['data'], $goodsPayload['event_type'], $goodsPayload['sign_type']),
    'syncGoods product_id 为字符串' => $goodsPayload['product_id'] === '1001',
    'syncGoods 携带商品名' => $goodsPayload['g_name'] === '矿泉水',
    'syncGoods 携带零售价' => $goodsPayload['retail_price'] === '2.50',
    'syncGoods status 透传' => $goodsPayload['status'] === '1',
    'syncGoods 签名 32 位小写' => preg_match('/^[a-f0-9]{32}$/', $goodsPayload['sign']) === 1,
    'syncGoods 签名固定向量正确' => $goodsPayload['sign'] === md5('test-secret' . '1001' . 'test-secret'),
    'syncGoods 签名可复算' => hash_equals($goodsPayload['sign'], $builder->makeSyncGoodsSign(1001)),
    '删除以 status=0 表达下架' => $deletePayload['status'] === '0' && $deletePayload['product_id'] === '1001',
    '设备报文为扁平行级结构' => !isset($machinePayload['data'], $machinePayload['event_type'], $machinePayload['sign_type']),
    '设备 machine_id 保持字符串' => $machinePayload['machine_id'] === 'M10001',
    '设备 product_id 为字符串' => $machinePayload['product_id'] === '1001',
    '设备携带货道编号' => $machinePayload['channel_code'] === 'A01',
    '设备库存写入 quantity/stock' => $machinePayload['quantity'] === 8 && $machinePayload['stock'] === 8,
    '设备售价为字符串' => $machinePayload['sale_price'] === '2.50',
    '设备签名 32 位小写' => preg_match('/^[a-f0-9]{32}$/', $machinePayload['sign']) === 1,
    '设备签名固定向量正确' => $machinePayload['sign'] === md5('test-secret' . 'M10001' . '1001' . 'test-secret'),
    '设备签名可复算' => hash_equals($machinePayload['sign'], $builder->makeMachineProductSign('M10001', 1001)),
    '批量构造返回全部有效明细' => count($machinePayloads) === 2,
    '无商品货道被跳过' => isset($machinePayloads[1]) && $machinePayloads[1]['channel_code'] === 'A02',
];

$failed = [];
foreach ($checks as $name => $passed) {
    if (!$passed) {
        $failed[] = $name;
    }
}
if ($failed) {
    fwrite(STDERR, "FAILED:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "OK: third-party product sync payload tests passed (" . count($checks) . " checks)\n";

