<?php

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Machine/Receive/ApiClient.php');
$goodsModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Goods/GoodsModel.php');
$failures = [];

$goodsStart = strpos($client, 'public function goods()');
$goodsFindStart = strpos($client, 'public function goodsFind()');
$goodsMethod = $goodsStart !== false && $goodsFindStart !== false
    ? substr($client, $goodsStart, $goodsFindStart - $goodsStart)
    : '';

if (!$goodsMethod) {
    $failures[] = '未找到终端 getGoods 对应的 goods 方法';
} elseif (strpos($goodsMethod, "'g.g_id desc'") === false) {
    $failures[] = '终端 getGoods 未按 g_id 倒序查询';
}
if (strpos($goodsMethod, "'g.update_time desc'") !== false) {
    $failures[] = '终端 getGoods 仍按更新时间倒序查询';
}

$joinListStart = strpos($goodsModel, 'public static function joinMachineGoodsList');
$joinFindStart = strpos($goodsModel, 'public static function joinMachineGoodsFind');
$joinListMethod = $joinListStart !== false && $joinFindStart !== false
    ? substr($goodsModel, $joinListStart, $joinFindStart - $joinListStart)
    : '';

if (!$joinListMethod) {
    $failures[] = '未找到商品关联设备列表查询方法';
} elseif (strpos($joinListMethod, "[['g_id', 'in', array_values(array_unique(\$gIds))]]") === false) {
    $failures[] = '商品多语言数据未按当前页 g_id 批量查询';
}
if (preg_match('/->each\(function \(\$item\).*GoodsLangModel::getList/s', $joinListMethod)) {
    $failures[] = '分页商品列表仍存在逐商品查询多语言数据的 N+1 问题';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] /machine/receive/getGoods 按 g_id 倒序查询\n";
echo "[PASS] 分页商品多语言数据使用批量查询\n";
echo "\nSummary: passed=2, failed=0\n";
