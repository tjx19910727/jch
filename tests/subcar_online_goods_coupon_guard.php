<?php

$apiFile = __DIR__ . '/../app/AppFactory/Machine/Receive/ApiClient.php';
$couponFile = __DIR__ . '/../app/AppFactory/Kernel/Traits/Activity/ActivityCouponTrait.php';
$api = file_get_contents($apiFile);
$coupon = file_get_contents($couponFile);
$normalizedApi = preg_replace('/\s+/', ' ', $api);

require_once __DIR__ . '/../vendor/autoload.php';
$matcher = new class {
    use \app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;

    public function matches($detail, $onlineGoods)
    {
        return $this->couponDetailMatchesOnlineGoods($detail, $onlineGoods);
    }

    public function isOnline($detail)
    {
        return $this->isOnlineActivityDetail($detail);
    }
};

$checks = [
    'subCar creates wc order snapshot' =>
        strpos($api, '$wc_order_no = [];') !== false,
    'subCar resets wc snapshot for every cart item' =>
        strpos($normalizedApi, "foreach (\$this->data['carList'] as \$value) { \$wc_order_no = [];") !== false,
    'coupon detail query includes wc snapshot' =>
        strpos($coupon, 'g_id,wc_order_no') !== false,
    'coupon keeps core goods matching separate' =>
        strpos($coupon, "\$acg_id = array_column(\$ac['ag'] ?? [], 'g_id')") !== false
        && strpos($coupon, 'couponDetailIsEligible($value, $ac, $acg_id)') !== false,
    'ordinary detail is not classified as online' =>
        !$matcher->isOnline(['g_id' => 1001, 'wc_order_no' => '']),
    'valid wc snapshot is classified as online' =>
        $matcher->isOnline(['g_id' => 9999, 'wc_order_no' => json_encode(['B' => ['out_no' => 'WC-PARENT-001']])]),
    'online detail matches configured parent source number' =>
        $matcher->matches([
            'g_id' => 9999,
            'wc_order_no' => json_encode(['B' => ['out_no' => 'WC-PARENT-001']]),
        ], [['source_no' => 'WC-PARENT-001']]),
    'online detail does not match another parent source number' =>
        !$matcher->matches([
            'g_id' => 9999,
            'wc_order_no' => json_encode(['B' => ['out_no' => 'WC-PARENT-001']]),
        ], [['source_no' => 'WC-PARENT-002']]),
];

$failed = [];
foreach ($checks as $name => $passed) {
    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed) {
    fwrite(STDERR, "subCar online goods coupon guard failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "subCar online goods coupon guard passed\n";
