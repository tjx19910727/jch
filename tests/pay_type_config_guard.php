<?php

/**
 * pay_type 支付类型配置表守卫。
 */

$root = dirname(__DIR__);
$model = file_get_contents($root . '/app/AppFactory/Kernel/Model/Payment/PayTypeModel.php');
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Payment/PayTypeTrait.php');
$saleTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/SaleOrders/SaleOrdersTrait.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Config/PayTypeClient.php');
$controller = file_get_contents($root . '/app/management/controller/config/PayType.php');
$validator = file_get_contents($root . '/app/management/validate/Config/VPayType.php');
$provider = file_get_contents($root . '/app/AppFactory/Kernel/Providers/Management/ConfigProvider.php');
$revenueDescTrait = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenuePayTypeDescTrait.php');
$sql = file_get_contents($root . '/文档说明/pay_type支付类型配置数据库变更.sql');
$openApiPath = $root . '/文档说明/pay_type支付类型配置接口.apifox.openapi.json';
$openApi = json_decode(file_get_contents($openApiPath), true);
$failures = [];

$checks = [
    'model maps pay_type table' =>
        strpos($model, 'protected $name = "pay_type"') !== false
        && strpos($model, '"pay_type_name" => "string"') !== false
        && strpos($model, '"pay_scene" => "int"') !== false,
    'trait provides CRUD and table map cache' =>
        strpos($trait, 'getPayTypeNameMapFromTable') !== false
        && strpos($trait, 'PayTypeModel::getList') !== false
        && strpos($trait, 'PayTypeModel::create') !== false
        && strpos($trait, 'PayTypeModel::destroy') !== false,
    'sale order pay type names prefer table with config fallback' =>
        strpos($saleTrait, 'use PayTypeTrait;') !== false
        && strpos($saleTrait, '$this->getPayTypeNameMapFromTable(false)') !== false
        && strpos($saleTrait, '$this->getPayTypeNameMapFromTable(true)') !== false
        && strpos($saleTrait, "config('payment.pay_type_map')") !== false
        && strpos($saleTrait, '$this->getPayTypeNameMap(true)') !== false,
    'revenue pay type desc uses table source' =>
        strpos($revenueDescTrait, 'use PayTypeTrait;') !== false
        && strpos($revenueDescTrait, '$this->getPayTypeNameMapFromTable(false)') !== false,
    'management client exposes add update del list find' =>
        strpos($client, 'addData') !== false
        && strpos($client, 'updateData') !== false
        && strpos($client, 'delData') !== false
        && strpos($client, 'getList') !== false
        && strpos($client, 'getFind') !== false
        && strpos($client, 'getTree') !== false
        && strpos($client, "'线上支付'") !== false
        && strpos($client, "'线下支付'") !== false,
    'controller exposes backend endpoints' =>
        strpos($controller, 'function getList') !== false
        && strpos($controller, 'function getFind') !== false
        && strpos($controller, 'function getTree') !== false
        && strpos($controller, 'function add') !== false
        && strpos($controller, 'function update') !== false
        && strpos($controller, 'function del') !== false,
    'validator covers unique business fields' =>
        strpos($validator, '"pay_type" => "require|number"') !== false
        && strpos($validator, '"pay_type_name" => "require|max:50"') !== false
        && strpos($validator, '"pay_scene" => "in:1,2"') !== false
        && strpos($validator, '"status" => "in:1,2"') !== false,
    'provider registers payType client' =>
        strpos($provider, "new PayTypeClient(\$app)") !== false
        && strpos($provider, "\$app['payType']") !== false,
    'sql creates table and permission nodes' =>
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `pay_type`') !== false
        && strpos($sql, 'UNIQUE KEY `uk_pay_type` (`pay_type`)') !== false
        && strpos($sql, '`pay_scene` tinyint NOT NULL DEFAULT 1') !== false
        && strpos($sql, 'ADD COLUMN `pay_scene` tinyint NOT NULL DEFAULT 1') !== false
        && strpos($sql, '/management/config.pay_type/getList') !== false
        && strpos($sql, '/management/config.pay_type/getTree') !== false
        && strpos($sql, '/management/config.pay_type/del') !== false,
    'apifox json parses and documents endpoints' =>
        json_last_error() === JSON_ERROR_NONE
        && isset($openApi['paths']['/management/config.pay_type/getList'])
        && isset($openApi['paths']['/management/config.pay_type/getTree'])
        && isset($openApi['paths']['/management/config.pay_type/add'])
        && isset($openApi['components']['securitySchemes']['TokenAuth'])
        && isset($openApi['components']['schemas']['PayTypeAddRequest']['properties']['pay_scene'])
        && isset($openApi['components']['schemas']['PayTypeTreeNode']),
];

foreach ($checks as $name => $ok) {
    if (!$ok) $failures[] = $name;
}

$expected = [
    0 => '免支付',
    1 => '微信支付',
    11 => '微信扫码支付',
    12 => '微信反扫支付',
    2 => '支付宝支付',
    21 => '支付宝扫码支付',
    22 => '支付宝反扫支付',
    4 => '京东收银',
    5 => '会员支付',
    6 => '丽呈线上支付',
    7 => '机器人线上支付',
    8 => 'COGOLINK',
    9 => '商场积分支付',
    10 => '八达通支付',
    20 => '余额支付',
    33 => '国际银联支付',
    34 => '八达通支付',
    35 => '国际银联卡支付',
    36 => '纸币支付',
    37 => '硬币支付',
];

foreach ($expected as $payType => $name) {
    if (strpos($sql, '(' . $payType . ", '" . $name . "'") === false) {
        $failures[] = "sql missing seed pay_type {$payType}";
    }
}

foreach ([12, 22, 8, 9, 10, 33, 34, 35, 36, 37] as $offlinePayType) {
    if (strpos($sql, '(' . $offlinePayType . ", '" . $expected[$offlinePayType] . "', 2,") === false) {
        $failures[] = "sql missing offline scene for pay_type {$offlinePayType}";
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] pay_type config table, CRUD endpoints, SQL seed and Apifox are aligned\n";
echo "\nSummary: passed=" . count($checks) . ", failed=0\n";
