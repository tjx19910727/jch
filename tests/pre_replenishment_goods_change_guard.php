<?php

$root = dirname(__DIR__);
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Machine/MachinePreReplenishmentTrait.php');
$goodsChangeTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Machine/MachinePreReplenishmentGoodsChangeTrait.php');
$snapshotTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Machine/MachinePreReplenishmentGoodsSnapshotTrait.php');
$management = file_get_contents($root . '/app/AppFactory/Management/Machine/MachinePreReplenishmentClient.php');
$api = file_get_contents($root . '/app/AppFactory/Machine/Receive/ApiClient.php');

if (!function_exists('json2arr')) {
    function json2arr($value)
    {
        return is_array($value) ? $value : json_decode((string)$value, true);
    }
}

require_once $root . '/app/AppFactory/Kernel/Traits/Machine/MachinePreReplenishmentGoodsSnapshotTrait.php';
require_once $root . '/app/AppFactory/Kernel/Traits/Machine/MachinePreReplenishmentGoodsChangeTrait.php';
require_once $root . '/app/AppFactory/Kernel/Traits/Machine/MachinePreReplenishmentTrait.php';

class PreReplenishmentGoodsSnapshotProbe
{
    use \app\AppFactory\Kernel\Traits\Machine\MachinePreReplenishmentGoodsSnapshotTrait;

    public function resolve($detail, $sourceGoods, $goodsMap)
    {
        return $this->resolvePreReplenishmentGoodsContext($detail, $sourceGoods, $goodsMap);
    }
}

class PreReplenishmentDetailNormalizeProbe
{
    use \app\AppFactory\Kernel\Traits\Machine\MachinePreReplenishmentTrait;

    public function normalize($details)
    {
        return $this->normalizeDetails($details);
    }
}

$goodsMap = [
    1 => ['g_id' => 1, 'sku' => 'SKU-A', 'g_name' => 'A'],
    2 => ['g_id' => 2, 'sku' => 'SKU-B', 'g_name' => 'B'],
];
$probe = new PreReplenishmentGoodsSnapshotProbe();
$changeContext = $probe->resolve(
    ['before_g_id' => 1, 'before_sku' => 'SKU-A', 'g_id' => 2, 'sku' => 'SKU-B'],
    $goodsMap[1],
    $goodsMap
);
$noChangeContext = $probe->resolve(
    ['before_g_id' => 1, 'before_sku' => 'SKU-A', 'g_id' => 1, 'sku' => 'SKU-A'],
    $goodsMap[1],
    $goodsMap
);
$normalizeProbe = new PreReplenishmentDetailNormalizeProbe();
$normalizedQueue = $normalizeProbe->normalize([[
    'machine_id' => 'M1',
    'mc_id' => 100,
    'after_g_id' => 2,
    'plan_quantity' => 3,
    'batch_arr' => [
        ['batch_id' => 11, 'is_head' => 1, 'sequence' => 1, 'g_id' => 1, 'plan_quantity' => 3],
        ['batch_id' => 12, 'is_head' => 2, 'sequence' => 2, 'g_id' => 2, 'plan_quantity' => 0],
    ],
]]);

$checks = [
    'detail stores source batch and target batch identity' => strpos($trait, "'batch_id' => (int)\$batchId") !== false
        && strpos($trait, "'target_batch_id' => 0") !== false
        && strpos($trait, "'batch_sequence' => (int)\$batchSequence") !== false,
    'detail stores source and target goods' => strpos($trait, "'before_g_id' => (int)\$beforeGId") !== false
        && strpos($trait, "'g_id' => (int)\$targetGoods['g_id']") !== false,
    'actual_g_id contract remains removed' => strpos(
        $trait . $snapshotTrait . $management . $api,
        'actual_g_id'
    ) === false,
    'after_g_id input supports explicit target selection' => strpos(
        $trait,
        "array_key_exists('after_g_id', \$item)"
    ) !== false,
    'management checks device feature before channel feature' => strpos(
        $management,
        '$machineMultiGoodsMap'
    ) !== false,
    'management returns full queue including head' => strpos(
        $management,
        "'batch_arr' => \$batchArr"
    ) !== false,
    'full queue normalization keeps one head and all batches' => count($normalizedQueue) === 2
        && $normalizedQueue[0]['batch_id'] === 11
        && $normalizedQueue[0]['is_head'] === 1
        && $normalizedQueue[0]['after_g_id'] === 2
        && $normalizedQueue[1]['batch_id'] === 12
        && $normalizedQueue[1]['is_head'] === 2,
    'editing preserves source batch snapshots' => strpos(
        $management,
        "'batch:' . (int)\$originalDetail['batch_id']"
    ) !== false,
    'device confirmation has ordinary goods change path' => strpos(
        $api,
        'applyPreReplenishmentGoodsChange('
    ) !== false,
    'device confirmation rebuilds a complete multi queue' => strpos(
        $api,
        'confirmPreReplenishmentMultiQueueV2('
    ) !== false
        && strpos($api, "->update(['status' => 4, 'stock' => 0, 'frozen_stock' => 0])") !== false
        && strpos($api, "'target_batch_id' => \$targetBatchIds[\$index]") !== false,
    'legacy multi orders remain compatible' => strpos(
        $api,
        '尚未保存 batch_id 的多商品预补货单'
    ) !== false,
    'goods change implementation remains isolated' => strpos(
        $goodsChangeTrait,
        'trait MachinePreReplenishmentGoodsChangeTrait'
    ) !== false,
    'goods context keeps source and target identity' => $changeContext['before_g_id'] === 1
        && $changeContext['g_id'] === 2
        && $changeContext['is_change_goods'] === true,
    'goods context identifies unchanged goods' => $noChangeContext['before_g_id'] === 1
        && $noChangeContext['g_id'] === 1
        && $noChangeContext['is_change_goods'] === false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) {
        $failed[] = $name;
    }
}

exit($failed ? 1 : 0);
