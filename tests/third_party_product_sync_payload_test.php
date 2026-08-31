<?php

require_once dirname(__DIR__) . '/app/AppFactory/Kernel/Service/Api/ThirdPartyProductSyncPayloadBuilder.php';

use app\AppFactory\Kernel\Service\Api\ThirdPartyProductSyncPayloadBuilder;

$builder = new ThirdPartyProductSyncPayloadBuilder('jch-test', 'test-secret');
$machinePayload = $builder->buildMachineInventory(
    'M10001',
    [['product_id' => 1001, 'quantity' => 8]],
    3,
    '11111111-2222-3333-4444-555555555555',
    1788123456
);
$goodsPayload = $builder->buildCoreGoods(
    1001,
    ['g_name' => '矿泉水'],
    'upsert',
    4,
    'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
    1788123457
);
$deletePayload = $builder->buildCoreGoods(
    1001,
    [],
    'delete',
    5,
    'ffffffff-1111-2222-3333-444444444444',
    1788123458
);

$checks = [
    '设备事件类型正确' => $machinePayload['event_type'] === 'machine_inventory.sync',
    '设备使用完整快照模式' => $machinePayload['data']['sync_mode'] === 'snapshot',
    '设备编号保持字符串' => $machinePayload['data']['machine_id'] === 'M10001',
    '设备货道列表保持数组' => count($machinePayload['data']['items']) === 1,
    '核心商品事件类型正确' => $goodsPayload['event_type'] === 'core_goods.sync',
    '核心商品使用增量模式' => $goodsPayload['data']['sync_mode'] === 'delta',
    '核心商品 upsert 操作正确' => $goodsPayload['data']['items'][0]['operation'] === 'upsert',
    '核心商品删除墓碑正确' => $deletePayload['data']['items'][0] === ['operation' => 'delete', 'product_id' => 1001],
    '签名格式正确' => preg_match('/^[a-f0-9]{64}$/', $machinePayload['sign']) === 1,
    '固定 HMAC 测试向量正确' => $machinePayload['sign'] === '2836e2f155d5a842c68d634a1c2fa39c80739dfe36c16ffc4fd073ae63840aed',
    '签名可重复计算' => hash_equals($machinePayload['sign'], $builder->makeSign($machinePayload)),
    '不同数据签名不同' => $machinePayload['sign'] !== $goodsPayload['sign'],
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
