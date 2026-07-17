<?php

$root = dirname(__DIR__);
$couponClient = file_get_contents($root . '/app/AppFactory/Management/Activity/ActivityCouponClient.php');
$couponAddStart = strpos($couponClient, 'public function addAc($postData)');
$couponUpdateStart = strpos($couponClient, 'public function updateAc($postData)');
$couponAdd = substr($couponClient, $couponAddStart, $couponUpdateStart - $couponAddStart);
$couponRuntime = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Activity/ActivityCouponTrait.php');
$fdClient = file_get_contents($root . '/app/AppFactory/Management/Activity/ActivityFdClient.php');
$fdRuntime = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Activity/ActivityFdTrait.php');
$sql = file_get_contents($root . '/文档说明/营销活动关联微程线上商品数据库变更.sql');
$openapi = json_decode(file_get_contents($root . '/文档说明/营销活动关联微程线上商品接口.apifox.openapi.json'), true);

$checks = [
    'coupon accepts onlineGoodsList' => strpos($couponClient, "onlineGoodsList") !== false,
    'coupon query separates onlineGoodsList' => substr_count($couponClient, "['onlineGoodsList'] = \$this->getActivityGoodsList") >= 2
        && substr_count($couponClient, "['goods_source' => 2]") >= 2,
    'coupon online goods ignore designated_goods' => strpos($couponClient, 'if ($hasOnlineGoodsList) {') !== false
        && strpos($couponClient, "\$onlineGoodsList = \$this->normalizeOnlineGoodsList(\$postData['onlineGoodsList'])") !== false,
    'coupon accepts comma separated online goods' => strpos($couponClient, 'protected function normalizeOnlineGoodsList') !== false
        && strpos($couponClient, "explode(',', \$value)") !== false,
    'coupon add saves online goods independently' => strpos($couponAdd, "\$onlineGoodsList = \$this->normalizeOnlineGoodsList(\$postData['onlineGoodsList'])") !== false
        && substr_count($couponAdd, 'addOnlineAg($insert, $onlineGoodsList)') === 1
        && strpos($couponAdd, "if (\$postData['designated_goods'] == 2 || \$postData['designated_goods'] == 3) {")
            < strpos($couponAdd, 'addOnlineAg($insert, $onlineGoodsList)'),
    'coupon stores online source number' => strpos($couponRuntime, "source_no") !== false,
    'coupon runtime matches wc out number' => strpos($couponRuntime, "detailSourceNos") !== false,
    'fd resolves wc goods configuration' => strpos($fdClient, "getWcGoodsFind(['no' => \$sourceNo])") !== false,
    'fd list and find return onlineGoodsList' => strpos($fdClient, 'public function getList(') !== false
        && strpos($fdClient, 'appendFdGoodsLists') !== false
        && strpos($fdClient, "\$fd['onlineGoodsList'][] = \$item") !== false,
    'fd accepts comma separated online goods' => strpos($fdClient, 'normalizeOnlineGoodsList') !== false
        && strpos($fdClient, "explode(',', \$value)") !== false
        && strpos($fdClient, "array_key_exists('onlineGoodsList', \$postData)") !== false,
    'fd add ignores copied primary keys' => strpos($fdClient, "unset(\$postData['fd_id'],\$postData['delContent']") !== false
        && strpos($fdClient, "unset(\$value['fdc_id'], \$value['fd_id'])") !== false,
    'fd runtime matches wc order snapshot' => strpos($fdRuntime, 'fdDetailMatchesOnlineGoods') !== false,
    'fd amount rules support configured online goods' => strpos($fdRuntime, 'fdOnlineGoodsMatch') !== false
        && strpos($fdRuntime, "\$contentWhere['goods_source'] = 1") !== false
        && strpos($fdRuntime, '线上商品不适用当前满减活动') !== false
        && strpos($fdRuntime, '线上商品订单不支持当前满减活动') === false,
    'migration covers coupon and fd tables' => strpos($sql, 'activity_goods') !== false && strpos($sql, 'activity_fd_content') !== false,
    'openapi documents four query interfaces' => is_array($openapi)
        && isset($openapi['paths']['/management/activity.activity_coupon/getList']['post'])
        && isset($openapi['paths']['/management/activity.activity_coupon/getFind']['post'])
        && isset($openapi['paths']['/management/activity.activity_fd/getList']['post'])
        && isset($openapi['paths']['/management/activity.activity_fd/getFind']['post']),
    'openapi documents onlineGoodsList and token' => isset($openapi['components']['schemas']['CouponActivity']['properties']['onlineGoodsList'])
        && isset($openapi['components']['schemas']['FdActivity']['properties']['onlineGoodsList'])
        && strpos($openapi['components']['schemas']['CouponSaveRequest']['properties']['onlineGoodsList']['description'] ?? '', 'designated_goods') !== false
        && ($openapi['components']['schemas']['CouponSaveRequest']['properties']['onlineGoodsList']['type'] ?? '') === 'string'
        && ($openapi['components']['parameters']['Token']['schema']['example'] ?? '') === '{{token}}',
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
    if (!$passed) exit(1);
}

echo "activity online goods config guard passed\n";
