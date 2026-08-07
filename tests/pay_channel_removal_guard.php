<?php

/**
 * pay_channel 字段删除守卫。
 */

$root = dirname(__DIR__);
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/SaleOrders/SaleOrdersTrait.php');
$paymentConfig = file_get_contents($root . '/config/payment.php');
$calculator = file_get_contents($root . '/app/AppFactory/Kernel/Service/Revenue/RevenueCalculator.php');
$saleOrdersClient = file_get_contents($root . '/app/AppFactory/Management/Sale/SaleOrdersClient.php');
$saleOrdersController = file_get_contents($root . '/app/management/controller/sale/SaleOrders.php');
$deleteSql = file_get_contents($root . '/文档说明/删除pay_channel字段数据库变更.sql');
$failures = [];

$deletedPaths = [
    '/app/AppFactory/Kernel/Model/Payment/PaymentPayTypeChannelRelationModel.php',
    '/文档说明/pay_type_pay_channel关系记录表.sql',
    '/文档说明/pay_type_pay_channel关系说明.md',
];

foreach ($deletedPaths as $path) {
    if (file_exists($root . $path)) {
        $failures[] = "历史 pay_type/pay_channel 关系文件未删除：{$path}";
    }
}

$checks = [
    'SaleOrdersTrait no longer writes pay_channel' =>
        strpos($trait, 'buildOrderPayChannel') === false
        && strpos($trait, 'appendOrderPayChannel') === false
        && strpos($trait, 'pay_channel') === false,
    'payment config no longer exposes pay_channel_map' =>
        strpos($paymentConfig, 'pay_channel_map') === false,
    'revenue calculator gates by pay_type' =>
        strpos($calculator, "\$this->order['pay_type']") !== false
        && strpos($calculator, "['pay_type' => \$payType, 'status' => 1]") !== false,
    'sale order query and export remove pay_channel' =>
        strpos($saleOrdersClient, 'pay_channel') === false
        && strpos($saleOrdersController, 'pay_channel') === false,
    'delete sql drops old columns and relation table' =>
        strpos($deleteSql, 'DROP COLUMN `pay_channel`') !== false
        && strpos($deleteSql, 'DROP COLUMN `pay_channel_name`') !== false
        && strpos($deleteSql, 'DROP TABLE IF EXISTS `payment_pay_type_channel_relation`') !== false,
];

foreach ($checks as $name => $ok) {
    if (!$ok) {
        $failures[] = $name;
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] pay_channel field writes, queries and relation artifacts removed\n";
echo "\nSummary: passed=" . count($checks) . ", failed=0\n";
