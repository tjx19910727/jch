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
$machinePayload = $builder->buildMachineInventory(
    'M10001',
    [['product_id' => 1001, 'quantity' => 8]],
    3,
    '11111111-2222-3333-4444-555555555555',
    1788123456
);

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
    '设备事件类型正确' => $machinePayload['event_type'] === 'machine_inventory.sync',
    '设备使用完整快照模式' => $machinePayload['data']['sync_mode'] === 'snapshot',
    '设备编号保持字符串' => $machinePayload['data']['machine_id'] === 'M10001',
    '设备货道列表保持数组' => count($machinePayload['data']['items']) === 1,
    '设备信封签名可重复计算' => hash_equals($machinePayload['sign'], $builder->makeSign($machinePayload)),
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

