<?php

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Pay/SaleOrders/PaymentClient.php');
$authCode = file_get_contents($root . '/app/AppFactory/Kernel/Support/AuthCode.php');

$checks = [
    '京东付款码映射仅包含微信和支付宝' =>
        preg_match('/protected \\$jdPayType = \\[\\s*1 => "WX",\\s*2 => "ALIPAY",\\s*\\];/', $client) === 1,
    '京东映射前校验付款码类型存在' =>
        strpos($client, 'if (!isset($this->jdPayType[$paymentType])) {') !== false,
    '京东付款码校验发生在类型映射之前' =>
        strpos($client, 'if (!isset($this->jdPayType[$paymentType])) {')
        < strpos($client, '$this->payType = $this->jdPayType[$paymentType];'),
    '京东非法付款码返回明确业务错误' =>
        strpos($client, 'return $this->rFail($this->lang("VOrderPay.unKnow_auth_code"));') !== false,
    '保留商场会员积分付款码兼容逻辑' =>
        strpos($authCode, 'return 9;') !== false,
];

$failures = [];
foreach ($checks as $name => $ok) {
    if (!$ok) $failures[] = $name;
}

if ($failures) {
    fwrite(STDERR, "[FAIL] jd cashier auth code guard\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "[PASS] jd cashier auth code guard\n";
