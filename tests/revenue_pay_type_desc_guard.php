<?php

/**
 * 新分账后台接口支付类型说明返回守卫。
 */

$root = dirname(__DIR__);
$trait = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenuePayTypeDescTrait.php');
$payChannelClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenuePayChannelClient.php');
$orderClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueOrderClient.php');
$openApi = json_decode(file_get_contents($root . '/文档说明/新分账后台接口.apifox.openapi.json'), true);
$failures = [];

if (!function_exists('config')) {
    function config($name)
    {
        return $name === 'payment.pay_type_map'
            ? [0 => '免支付', 1 => '微信支付', 20 => '余额支付']
            : null;
    }
}

require_once $root . '/app/AppFactory/Management/Revenue/RevenuePayTypeDescTrait.php';

$formatter = new class {
    use \app\AppFactory\Management\Revenue\RevenuePayTypeDescTrait;

    public function format($data)
    {
        return $this->appendRevenuePayTypeDesc($data);
    }
};

$formatted = $formatter->format([
    'pay_type' => 0,
    'list' => [
        ['pay_type' => 1],
        ['pay_type' => 999],
    ],
]);

if (($formatted['pay_type_desc'] ?? '') !== '免支付'
    || ($formatted['list'][0]['pay_type_desc'] ?? '') !== '微信支付'
    || ($formatted['list'][1]['pay_type_desc'] ?? '') !== '支付类型#999') {
    $failures[] = '支付类型说明实际转换结果不符合配置映射或未知类型兜底约定';
}

foreach ([
    "config('payment.pay_type_map')",
    "\$field === 'pay_type'",
    "\$data['pay_type_desc']",
    "'支付类型#' . \$payType",
] as $expected) {
    if (strpos($trait, $expected) === false) {
        $failures[] = "支付类型说明补全逻辑缺少：{$expected}";
    }
}

foreach ([
    '分账支付通道接口' => $payChannelClient,
    '分账订单接口' => $orderClient,
] as $name => $client) {
    if (strpos($client, 'use RevenuePayTypeDescTrait;') === false
        || strpos($client, 'appendRevenuePayTypeDesc(') === false) {
        $failures[] = "{$name}没有接入 pay_type_desc 补全逻辑";
    }
}

$responseFields = $openApi['x-response-pay-type-fields']['fields'] ?? [];
if (($responseFields['pay_type'] ?? '') !== 'pay_type_desc') {
    $failures[] = 'OpenAPI 未声明 pay_type 与 pay_type_desc 返回约定';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] revenue 后台接口按 payment.pay_type_map 补全 pay_type_desc\n";
echo "[PASS] 支付类型说明支持免支付、嵌套数据和未知类型兜底\n";
echo "[PASS] 支付通道和分账订单接口均已接入支付类型说明\n";
echo "[PASS] OpenAPI 已声明 pay_type_desc 返回约定\n";
echo "\nSummary: passed=4, failed=0\n";
