<?php

$root = dirname(__DIR__);
$fd = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Activity/ActivityFdTrait.php');
$orders = file_get_contents($root . '/app/AppFactory/Kernel/Traits/SaleOrders/SaleOrdersTrait.php');

require_once $root . '/vendor/autoload.php';
$calculator = new class {
    use \app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;

    public function clamp($discount, $amount)
    {
        return $this->clampFdDiscount($discount, $amount);
    }

    public function subtract($amount, $discount)
    {
        return $this->subtractFdDiscount($amount, $discount);
    }
};

$checks = [
    'fd clamps current discount to eligible balance' => strpos($fd, 'clampFdDiscount($this->countContent[\'discount_price\'], $discountBase)') !== false,
    'fd distributes current rather than cumulative discount' => strpos($fd, 'bcmul($currentDiscount, bcdiv(') !== false,
    'fd detail subtraction is non negative' => strpos($fd, 'subtractFdDiscount($dv[\'total_sod_price\'], $sodDiscountPrice)') !== false,
    'fd designated goods subtraction is non negative' => strpos($fd, 'subtractFdDiscount($this->sku[\'total_sod_price\'], $discount_price)') !== false,
    'order insert and update normalize non negative fields' =>
        substr_count($orders, 'normalizeSaleOrderNonNegativeFields(') >= 3,
    'detail insert and update normalize non negative fields' =>
        substr_count($orders, 'normalizeSaleOrderDetailNonNegativeFields(') >= 3,
    'discount larger than amount is capped' => bccomp($calculator->clamp('10', '5'), '5', 4) === 0,
    'subtraction cannot return a negative amount' => bccomp($calculator->subtract('5', '10'), '0', 4) === 0,
    'negative configured discount is ignored' => bccomp($calculator->clamp('-3', '5'), '0', 4) === 0,
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
    if (!$passed) exit(1);
}

echo "order discount non-negative guard passed\n";
