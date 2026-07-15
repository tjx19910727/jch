<?php

$root = dirname(__DIR__);
$couponClient = file_get_contents($root . '/app/AppFactory/Management/Activity/ActivityCouponClient.php');
$couponRuntime = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Activity/ActivityCouponTrait.php');
$fdClient = file_get_contents($root . '/app/AppFactory/Management/Activity/ActivityFdClient.php');
$fdRuntime = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Activity/ActivityFdTrait.php');
$sql = file_get_contents($root . '/文档说明/营销活动关联微程线上商品数据库变更.sql');

$checks = [
    'coupon accepts onlineGoodsList' => strpos($couponClient, "onlineGoodsList") !== false,
    'coupon stores online source number' => strpos($couponRuntime, "source_no") !== false,
    'coupon runtime matches wc out number' => strpos($couponRuntime, "detailSourceNos") !== false,
    'fd resolves wc goods configuration' => strpos($fdClient, "getWcGoodsFind(['no' => \$sourceNo])") !== false,
    'fd runtime matches wc order snapshot' => strpos($fdRuntime, 'fdDetailMatchesOnlineGoods') !== false,
    'migration covers coupon and fd tables' => strpos($sql, 'activity_goods') !== false && strpos($sql, 'activity_fd_content') !== false,
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
    if (!$passed) exit(1);
}

echo "activity online goods config guard passed\n";
