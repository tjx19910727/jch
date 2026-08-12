<?php

/**
 * 本次支付类型、订单类型及设备端支付类型接口守卫。
 */

$root = dirname(__DIR__);
$files = [
    'payTypeController' => $root . '/app/management/controller/config/PayType.php',
    'orderTypeController' => $root . '/app/management/controller/config/OrderType.php',
    'payTypeClient' => $root . '/app/AppFactory/Management/Config/PayTypeClient.php',
    'orderTypeClient' => $root . '/app/AppFactory/Management/Config/OrderTypeClient.php',
    'payTypeValidator' => $root . '/app/management/validate/Config/VPayType.php',
    'orderTypeValidator' => $root . '/app/management/validate/Config/VOrderType.php',
    'receiveController' => $root . '/app/machine/controller/Receive.php',
    'receiveValidator' => $root . '/app/machine/validate/VReceive.php',
    'apiClient' => $root . '/app/AppFactory/Machine/Receive/ApiClient.php',
];

$content = [];
foreach ($files as $key => $path) {
    $content[$key] = file_get_contents($path);
}

$openApiPath = $root . '/文档说明/本次支付订单类型及设备支付类型接口.apifox.openapi.json';
$openApi = json_decode(file_get_contents($openApiPath), true);
$failures = [];

$checks = [
    'management pay_type controller exposes CRUD endpoints' =>
        strpos($content['payTypeController'], 'function getList') !== false
        && strpos($content['payTypeController'], 'function getFind') !== false
        && strpos($content['payTypeController'], 'function getTree') !== false
        && strpos($content['payTypeController'], 'function add') !== false
        && strpos($content['payTypeController'], 'function update') !== false
        && strpos($content['payTypeController'], 'function del') !== false,
    'management order_type controller exposes CRUD endpoints' =>
        strpos($content['orderTypeController'], 'function getList') !== false
        && strpos($content['orderTypeController'], 'function getFind') !== false
        && strpos($content['orderTypeController'], 'function add') !== false
        && strpos($content['orderTypeController'], 'function update') !== false
        && strpos($content['orderTypeController'], 'function del') !== false,
    'management clients implement read and write methods' =>
        strpos($content['payTypeClient'], 'public function getList') !== false
        && strpos($content['payTypeClient'], 'public function getTree') !== false
        && strpos($content['payTypeClient'], "'线上支付'") !== false
        && strpos($content['payTypeClient'], "'线下支付'") !== false
        && strpos($content['payTypeClient'], 'public function addData') !== false
        && strpos($content['orderTypeClient'], 'public function getList') !== false
        && strpos($content['orderTypeClient'], 'public function addData') !== false,
    'validators cover add update delete required fields' =>
        strpos($content['payTypeValidator'], '"add"') !== false
        && strpos($content['payTypeValidator'], 'sceneUpdate') !== false
        && strpos($content['payTypeValidator'], '"del" => ["pt_id"]') !== false
        && strpos($content['orderTypeValidator'], '"add"') !== false
        && strpos($content['orderTypeValidator'], 'sceneUpdate') !== false
        && strpos($content['orderTypeValidator'], '"del" => ["ot_id"]') !== false,
    'machine receive controller exposes pay_type list endpoint' =>
        strpos($content['receiveController'], 'function getPayTypeList') !== false
        && strpos($content['receiveController'], '$this->app->api->getPayTypeList()') !== false,
    'machine receive validator signs pay_type list endpoint' =>
        strpos($content['receiveValidator'], '"getPayTypeList" => ["msg_id","machine_id","timestamp","sign"]') !== false,
    'machine api client returns enabled pay_type list with fallback' =>
        strpos($content['apiClient'], 'public function getPayTypeList') !== false
        && strpos($content['apiClient'], "['status' => 1]") !== false
        && strpos($content['apiClient'], "config('payment.pay_type_map')") !== false
        && strpos($content['apiClient'], "'value' =>") !== false
        && strpos($content['apiClient'], "'label' =>") !== false,
    'apifox json parses' =>
        json_last_error() === JSON_ERROR_NONE,
];

foreach ($checks as $name => $ok) {
    if (!$ok) $failures[] = $name;
}

$expectedPaths = [
    '/management/config.pay_type/getList',
    '/management/config.pay_type/getFind',
    '/management/config.pay_type/getTree',
    '/management/config.pay_type/add',
    '/management/config.pay_type/update',
    '/management/config.pay_type/del',
    '/management/config.order_type/getList',
    '/management/config.order_type/getFind',
    '/management/config.order_type/add',
    '/management/config.order_type/update',
    '/management/config.order_type/del',
    '/machine/receive/getPayTypeList',
];

foreach ($expectedPaths as $path) {
    if (!isset($openApi['paths'][$path]['post'])) {
        $failures[] = "apifox missing path {$path}";
    }
}

if (!isset($openApi['components']['parameters']['TokenHeader'])
    || !isset($openApi['components']['parameters']['MacHeader'])
    || !isset($openApi['components']['schemas']['PayTypeTreeNode'])
    || !isset($openApi['components']['schemas']['MachinePayTypeItem'])) {
    $failures[] = 'apifox missing shared auth headers or machine schema';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] pay_type/order_type management endpoints and machine pay_type endpoint are documented and callable by code path\n";
echo "\nSummary: passed=" . count($checks) . ', paths=' . count($expectedPaths) . ", failed=0\n";
