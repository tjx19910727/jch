<?php

$apiFile = __DIR__ . '/../app/AppFactory/Machine/Receive/ApiClient.php';
$couponFile = __DIR__ . '/../app/AppFactory/Kernel/Traits/Activity/ActivityCouponTrait.php';
$api = file_get_contents($apiFile);
$coupon = file_get_contents($couponFile);

require_once __DIR__ . '/../vendor/autoload.php';
$matcher = new class {
    use \app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;

    public function matches($detail, $couponGoodsIds)
    {
        return $this->couponDetailMatchesGoods($detail, $couponGoodsIds);
    }
};

$checks = [
    'subCar creates wc order snapshot' =>
        strpos($api, '$wc_order_no = [];') !== false,
    'coupon detail query includes wc snapshot' =>
        strpos($coupon, 'g_id,wc_order_no') !== false,
    'coupon scope resolves ordinary and online goods ids' =>
        strpos($coupon, 'couponDetailMatchesGoods($value, $ac[\'ag\'])') !== false
        && strpos($coupon, '$detail[\'g_id\']') !== false
        && strpos($coupon, '$wcGoods[\'g_id\']') !== false,
    'designated coupon accepts matched online detail' =>
        strpos($coupon, '$ac[\'designated_goods\'] == 2 && $matchesDesignatedGoods') !== false,
    'excluded coupon rejects matched online detail' =>
        strpos($coupon, '$ac[\'designated_goods\'] == 3 && !$matchesDesignatedGoods') !== false,
    'ordinary detail still matches by detail goods id' =>
        $matcher->matches(['g_id' => 1001, 'wc_order_no' => ''], [1001]),
    'online detail matches by child goods id' =>
        $matcher->matches([
            'g_id' => 9999,
            'wc_order_no' => json_encode([
                'B' => ['g_id' => 1001],
                'C' => ['g_id' => 0],
            ]),
        ], [1001]),
    'online detail does not match unrelated goods id' =>
        !$matcher->matches([
            'g_id' => 9999,
            'wc_order_no' => json_encode(['B' => ['g_id' => 1001]]),
        ], [2002]),
    'online detail matches configured parent source number' =>
        $matcher->matches([
            'g_id' => 9999,
            'wc_order_no' => json_encode(['B' => ['out_no' => 'WC-PARENT-001']]),
        ], [['goods_source' => 2, 'source_no' => 'WC-PARENT-001']]),
    'online detail does not match another parent source number' =>
        !$matcher->matches([
            'g_id' => 9999,
            'wc_order_no' => json_encode(['B' => ['out_no' => 'WC-PARENT-001']]),
        ], [['goods_source' => 2, 'source_no' => 'WC-PARENT-002']]),
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
