<?php

$client = file_get_contents(__DIR__ . '/../app/AppFactory/Management/Activity/ActivityCouponClient.php');
$methodStart = strpos($client, 'public function updateAc($postData)');
$methodEnd = strpos($client, 'public function activeTakeDown', $methodStart);
$updateMethod = substr($client, $methodStart, $methodEnd - $methodStart);

$checks = [
    'update checks submitted coupon code' => strpos($updateMethod, "'code' => \$postData['code']") !== false,
    'update excludes current coupon id' => strpos($updateMethod, "['c_id', '<>', \$postData['c_id']]") !== false,
    'update still checks active and pending statuses' => strpos($updateMethod, "['status', 'in', [1, 2]]") !== false,
    'duplicate code response remains compatible' => strpos($updateMethod, '当前优惠码已存在，不能重复使用') !== false,
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
    if (!$passed) exit(1);
}

echo "activity coupon update code unique guard passed\n";
