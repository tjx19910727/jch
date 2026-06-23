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
        if ($name === 'payment.pay_type_map') {
            return [0 => '免支付', 1 => '微信支付', 20 => '余额支付'];
        }
        if ($name === 'payment.pay_channel_map') {
            return [7 => '微信支付', 8 => '支付宝支付', 11 => '其他'];
        }
        return null;
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
    'pay_channel' => 7,
    'list' => [
        ['pay_type' => 1],
        ['pay_channel' => 8],
        ['pay_type' => 999],
        ['pay_channel' => 999],
    ],
]);

if (($formatted['pay_type_desc'] ?? '') !== '免支付'
    || ($formatted['pay_channel_desc'] ?? '') !== '微信支付'
    || ($formatted['list'][0]['pay_type_desc'] ?? '') !== '微信支付'
    || ($formatted['list'][1]['pay_channel_desc'] ?? '') !== '支付宝支付'
    || ($formatted['list'][2]['pay_type_desc'] ?? '') !== '支付类型#999'
    || ($formatted['list'][3]['pay_channel_desc'] ?? '') !== '支付渠道#999') {
    $failures[] = '支付类型说明实际转换结果不符合配置映射或未知类型兜底约定';
}

foreach ([
    "config('payment.pay_type_map')",
    "config('payment.pay_channel_map')",
    "\$field === 'pay_type'",
    "\$field === 'pay_channel'",
    "\$data['pay_type_desc']",
    "\$data['pay_channel_desc']",
    "'支付类型#' . \$payType",
    "'支付渠道#' . \$payChannel",
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
        $failures[] = "{$name}没有接入支付类型/渠道说明补全逻辑";
    }
}

$responseFields = $openApi['x-response-pay-type-fields']['fields'] ?? [];
if (($responseFields['pay_type'] ?? '') !== 'pay_type_desc'
    || ($responseFields['pay_channel'] ?? '') !== 'pay_channel_desc') {
    $failures[] = 'OpenAPI 未声明 pay_type/pay_channel 与说明字段返回约定';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] revenue 后台接口按 payment 映射补全 pay_type_desc/pay_channel_desc\n";
echo "[PASS] 支付类型/渠道说明支持嵌套数据和未知类型兜底\n";
echo "[PASS] 支付通道和分账订单接口均已接入支付类型/渠道说明\n";
echo "[PASS] OpenAPI 已声明 pay_type_desc/pay_channel_desc 返回约定\n";
echo "\nSummary: passed=4, failed=0\n";
