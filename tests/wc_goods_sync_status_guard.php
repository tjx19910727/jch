<?php

$root = dirname(__DIR__);
$base = file_get_contents($root . '/app/AppFactory/Kernel/Traits/WeiCheng/WcBaseTrait.php');
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/WeiCheng/WcGoodsTrait.php');
$client = file_get_contents($root . '/app/AppFactory/Management/WeiCheng/WeiChengClient.php');
$sql = file_get_contents($root . '/数据库更新.sql');
$failures = [];

function checkWcGoodsSyncGuard($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

checkWcGoodsSyncGuard(
    strpos($base, 'synchronizeGoodsLists2Db($goods_lists, $type, $syncBatchNo =') !== false
        && strpos($base, "\$goods['sync_status'] = \$syncBatchNo . '_1'") !== false
        && strpos($base, "\$goods['is_pub']") === false,
    '列表同步应写入批次返回状态且不得修改 is_pub',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($base, 'synchronizeGoods2Db($updateData, $syncBatchNo =') !== false
        && strpos($base, "\$updateData['sync_status'] = \$syncBatchNo . '_1'") !== false,
    '详情同步应写入批次返回状态',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($client, "\$syncBatchNo = date('YmdHis')") !== false
        && strpos($client, 'synchronizeGoodsTypes($type[\'id\'], 1, $syncBatchNo, false)') !== false
        && strpos($client, 'markWcGoodsMissingFromSync($syncBatchNo)') !== false,
    '全分类同步应共用同一批次并在全部成功后标记未返回商品',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($client, 'markWcGoodsMissingFromSync($syncBatchNo, $goods_type)') !== false
        && strpos($trait, "\$query->where('type', '=', \$goodsType)") !== false,
    '单分类同步应只标记当前分类未返回商品',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($client, '微程分类同步失败，跳过未返回标记') !== false,
    '同步失败不应执行未返回商品标记',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($client, "\$offlineStatus = \$syncBatchNo . '_2'") !== false
        && strpos($client, 'getWcGoodsMissingSyncOutNos($onlineStatus, $goodsType)') !== false
        && strpos($client, 'updateWcGoodsMissingSyncStatus($onlineStatus, $offlineStatus, $goodsType)') !== false
        && strpos($trait, "whereRaw('(`sync_status` <> ? OR `sync_status` IS NULL)'") !== false
        && strpos($client, "'is_pub' => 2") === false,
    '差集标记只能写 sync_status，不能改 is_pub',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($client, 'markWcMachineGoodsOffShelf($missingOutNos)') !== false
        && strpos($trait, 'offShelfWcMachineGoodsByOutNos(array $outNos)') !== false
        && strpos($trait, "WcMachineGoodsModel::where('is_shelf', '=', 1)") !== false
        && strpos($trait, "->update(['is_shelf' => 2])") !== false,
    '未返回商品应将设备在线商品标记为下架',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($client, 'deleteWcMachineChannelByOutNos($missingOutNos)') !== false
        && strpos($trait, 'deleteWcMachineChannelByOutNos(array $outNos)') !== false
        && strpos($trait, "WcMachineChannelModel::where('out_no', 'in', \$outNos)->delete()") !== false,
    '未返回商品应删除设备虚拟货道绑定',
    $failures
);
checkWcGoodsSyncGuard(
    strpos($sql, 'wc_goods ADD sync_status varchar(32)') !== false
        || strpos($sql, 'ADD COLUMN `sync_status` varchar(32)') !== false,
    '数据库更新需包含 wc_goods.sync_status 字段',
    $failures
);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 微程商品同步批次状态守卫通过\n";

