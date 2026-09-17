<?php

// 守卫：设备商品库存变化统计接口（主统计 + 上架/下架/销售明细）
// 关键不变量：
//   1) 剩余库存必须沿用"货道库存汇总"口径（machine_goods 上的同名列是历史遗留、恒为 0）；
//   2) 累计上架/下架取补货单（machine_channel_replenishment.quantity 正负），累计销售取订单明细；
//   3) 必须返回等式字段（初始库存 + 累计上架 = 累计下架 + 累计销售 + 剩余库存）与四方对账差额；
//   4) 明细必须可溯源：补货单带同刻库存流水日志，销售明细带订单号；
//   5) 所有对外方法都要做设备数据范围校验。

$root = dirname(__DIR__);
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Machine/MachineGoodsStockChangeTrait.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Machine/MachineGoodsClient.php');
$controller = file_get_contents($root . '/app/management/controller/machine/MachineGoods.php');
$validator = file_get_contents($root . '/app/management/validate/Machine/VMachineGoods.php');
$stockFields = file_get_contents($root . '/app/AppFactory/Kernel/Model/Machine/MachineGoodsModel.php');
if ($trait === false || $client === false || $controller === false || $validator === false || $stockFields === false) {
    throw new RuntimeException('cannot read stock change files');
}

$checks = [
    '四个接口方法齐全（主统计 + 上架/下架/销售明细）' =>
        strpos($trait, 'public function getStockChangeStatsData(') !== false
        && strpos($trait, 'public function getStockChangeShelfDetailData(') !== false
        && strpos($trait, 'public function getStockChangeUnshelfDetailData(') !== false
        && strpos($trait, 'public function getStockChangeSaleDetailData(') !== false,

    '剩余库存沿用货道库存汇总口径（status=1 可用 / status>1 不可用 / frozen 预定）' =>
        strpos($trait, "->where('m_id', \$mId)->where('g_id', \$gId)") !== false
        && strpos($trait, 'SUM(CASE WHEN status = 1 THEN stock ELSE 0 END) available_stock') !== false
        && strpos($trait, 'SUM(CASE WHEN status > 1 THEN stock ELSE 0 END) disabled_stock') !== false
        && strpos($trait, 'SUM(frozen_stock) reserve_stock') !== false
        && strpos($stockFields, 'machine_goods 表上虽存在同名列，但属历史遗留且不再维护') !== false,

    '累计上架/下架取补货单正负数量' =>
        strpos($trait, "Db::name('machine_channel_replenishment')") !== false
        && strpos($trait, 'SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) shelved') !== false
        && strpos($trait, 'SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END) unshelved') !== false
        && strpos($trait, "\$quantityCondition = \$direction === 'shelved' ? ['>', 0] : ['<', 0];") !== false,

    '累计销售取订单明细（已支付 + 出货成功/未取，按出货时间）' =>
        strpos($trait, "->where('o.pay_status', 3)->whereIn('o.out_status', [4, 6])") !== false
        && strpos($trait, "->where('o.out_time', 'between', \$timeBetween)") !== false
        && strpos($trait, 'SUM(d.success_quantity) sold') !== false,

    '返回等式字段与对账差额（含 balanced 标记）' =>
        strpos($trait, "'opening_stock' => \$openingStock") !== false
        && strpos($trait, "'shelved_total' => \$shelved") !== false
        && strpos($trait, "'unshelved_total' => \$unshelved") !== false
        && strpos($trait, "'sold_total' => \$sold") !== false
        && strpos($trait, "'closing_stock' => \$closingStock") !== false
        && strpos($trait, "'balanced' => \$diff === 0") !== false
        && strpos($trait, '$openingStock = $closingStock - ($shelved - $unshelved - $sold);') !== false,

    '对账区块覆盖 单据/流水/订单/实物锚点 四个来源' =>
        strpos($trait, "'shelved_doc_vs_log'") !== false
        && strpos($trait, "'unshelved_doc_vs_log'") !== false
        && strpos($trait, "'sold_order_vs_log'") !== false
        && strpos($trait, "'stock_anchor_vs_ledger'") !== false
        && strpos($trait, "'goods_change_untimed'") !== false,

    '明细可溯源：补货单附同刻库存流水、销售明细带订单号' =>
        strpos($trait, "\$row['change_log'] = \$logMap[\$key] ?? null;") !== false
        && strpos($trait, 'o.trade_no') !== false
        && strpos($trait, 'd.sod_id') !== false,

    '查询条件校验：m_id/g_id/起止时间与倒置校验' =>
        strpos($trait, "if (\$mId <= 0) throw new \\InvalidArgumentException('设备ID不能为空');") !== false
        && strpos($trait, "if (\$gId <= 0) throw new \\InvalidArgumentException('商品ID不能为空');") !== false
        && strpos($trait, "if (\$startTime > \$endTime) throw new \\InvalidArgumentException('开始时间不能大于结束时间');") !== false
        && strpos($trait, "\$time = strtotime(date('Y-m-d', \$time) . ' 23:59:59');") !== false,

    '所有对外方法都做设备数据范围校验（主账号 ao_id / 子账号授权设备）' =>
        substr_count($client, '$this->assertStockChangeMachineScope(') === 2
        && strpos($client, "if (intval(\$this->manager['pid'] ?? 0) > 0)") !== false
        && strpos($client, "->getAuthManagerMachineColumn(") !== false,

    '控制器与校验器接线完整' =>
        strpos($controller, 'public function getStockChangeStats()') !== false
        && strpos($controller, 'public function getStockChangeShelfList()') !== false
        && strpos($controller, 'public function getStockChangeUnshelfList()') !== false
        && strpos($controller, 'public function getStockChangeSaleList()') !== false
        && strpos($controller, "'.stockChangeStats'") !== false
        && strpos($controller, "'.stockChangeDetail'") !== false
        && strpos($validator, '"stockChangeStats" => ["m_id", "g_id", "start_time", "end_time"]') !== false
        && strpos($validator, '"stockChangeDetail" => ["m_id", "g_id", "start_time", "end_time"]') !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
