<?php

/**
 * order_type 订单类型配置表守卫。
 */

$root = dirname(__DIR__);
$model = file_get_contents($root . '/app/AppFactory/Kernel/Model/SaleOrders/OrderTypeModel.php');
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/SaleOrders/OrderTypeTrait.php');
$saleTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/SaleOrders/SaleOrdersTrait.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Config/OrderTypeClient.php');
$controller = file_get_contents($root . '/app/management/controller/config/OrderType.php');
$validator = file_get_contents($root . '/app/management/validate/Config/VOrderType.php');
$provider = file_get_contents($root . '/app/AppFactory/Kernel/Providers/Management/ConfigProvider.php');
$saleClient = file_get_contents($root . '/app/AppFactory/Management/Sale/SaleOrdersClient.php');
$exportClient = file_get_contents($root . '/app/AppFactory/TimeTask/Export/ExportClient.php');
$sql = file_get_contents($root . '/文档说明/order_type订单类型配置数据库变更.sql');
$openApi = json_decode(file_get_contents($root . '/文档说明/order_type订单类型配置接口.apifox.openapi.json'), true);
$failures = [];

$checks = [
    'model maps order_type table' =>
        strpos($model, 'protected $name = "order_type"') !== false
        && strpos($model, '"order_type_name" => "string"') !== false,
    'trait provides CRUD and table map cache' =>
        strpos($trait, 'getOrderTypeNameMapFromTable') !== false
        && strpos($trait, 'OrderTypeModel::getList') !== false
        && strpos($trait, 'OrderTypeModel::create') !== false
        && strpos($trait, 'OrderTypeModel::destroy') !== false,
    'sale order trait exposes defaults, formatter and options' =>
        strpos($saleTrait, 'use OrderTypeTrait;') !== false
        && strpos($saleTrait, 'getDefaultOrderTypeNameMap') !== false
        && strpos($saleTrait, 'formatOrderType') !== false
        && strpos($saleTrait, 'getOrderTypeOptions') !== false,
    'management client exposes add update del list find' =>
        strpos($client, 'addData') !== false
        && strpos($client, 'updateData') !== false
        && strpos($client, 'delData') !== false
        && strpos($client, 'getList') !== false
        && strpos($client, 'getFind') !== false,
    'controller exposes five backend endpoints' =>
        strpos($controller, 'function getList') !== false
        && strpos($controller, 'function getFind') !== false
        && strpos($controller, 'function add') !== false
        && strpos($controller, 'function update') !== false
        && strpos($controller, 'function del') !== false,
    'validator covers required business fields' =>
        strpos($validator, '"order_type" => "require|number"') !== false
        && strpos($validator, '"order_type_name" => "require|max:50"') !== false
        && strpos($validator, '"status" => "in:1,2"') !== false,
    'provider registers orderType client' =>
        strpos($provider, "new OrderTypeClient(\$app)") !== false
        && strpos($provider, "\$app['orderType']") !== false,
    'sale export and options use order type map' =>
        strpos($saleClient, 'order_type_list') !== false
        && strpos($saleClient, 'formatOrderType($orderType)') !== false
        && strpos($saleClient, 'buildOrderTypeCaseSql') !== false
        && strpos($saleClient, '盲盒活动') === false,
    'time task export uses order type map' =>
        strpos($exportClient, 'buildOrderTypeCaseSql') !== false
        && strpos($exportClient, 'getOrderTypeNameMapFromTable(false)') !== false
        && strpos($exportClient, '盲盒活动') === false,
    'sql creates table and permission nodes' =>
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `order_type`') !== false
        && strpos($sql, 'UNIQUE KEY `uk_order_type` (`order_type`)') !== false
        && strpos($sql, 'FROM `sale_orders` so') !== false
        && strpos($sql, "CONCAT('订单类型#', so.`order_type`)") !== false
        && strpos($sql, '/management/config.order_type/getList') !== false
        && strpos($sql, '/management/config.order_type/del') !== false,
    'apifox json parses and documents endpoints' =>
        json_last_error() === JSON_ERROR_NONE
        && isset($openApi['paths']['/management/config.order_type/getList'])
        && isset($openApi['paths']['/management/config.order_type/add'])
        && isset($openApi['components']['securitySchemes']['TokenAuth']),
];

foreach ($checks as $name => $ok) {
    if (!$ok) $failures[] = $name;
}

$expected = [
    1 => '普通订单',
    2 => '优惠券订单',
    3 => '取货码订单',
    4 => '付费抽奖订单',
    5 => '满减满送订单',
    6 => '叠加营销活动订单',
    7 => '商场积分订单',
];

foreach ($expected as $orderType => $name) {
    if (strpos($sql, '(' . $orderType . ", '" . $name . "'") === false) {
        $failures[] = "sql missing seed order_type {$orderType}";
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] order_type config table, CRUD endpoints, SQL seed and Apifox are aligned\n";
echo "\nSummary: passed=" . count($checks) . ", failed=0\n";
