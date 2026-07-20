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

require_once $root . '/app/AppFactory/Kernel/Traits/Activity/ActivityFdTrait.php';
require_once $root . '/app/AppFactory/Kernel/Traits/Activity/ActivityCouponTrait.php';

class ActivityCouponOnlineGoodsMatchProbe
{
    use \app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;

    public function eligible($detail, $coupon)
    {
        return $this->couponDetailIsEligible($detail, $coupon);
    }
}

class ActivityFdOnlineGoodsMatchProbe
{
    use \app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;

    public function matches($details, $onlineContents)
    {
        return $this->fdOnlineGoodsMatch($details, $onlineContents);
    }

    public function matchedSodIds($details, $onlineContents)
    {
        return $this->getFdMatchedOnlineSodIds($details, $onlineContents);
    }

    public function eligibleDiscountDetails($details, $matchedSodIds, $conditionType = 1)
    {
        $this->fdMatchedOnlineSodIds = $matchedSodIds;
        $this->fd = ['condition_type' => $conditionType];
        return $this->getFdDiscountEligibleDetails($details);
    }
}

$fdMatchProbe = new ActivityFdOnlineGoodsMatchProbe();
$couponMatchProbe = new ActivityCouponOnlineGoodsMatchProbe();
$couponScope = [
    'designated_goods' => 1,
    'ag' => [],
    'onlineAg' => [['source_no' => 'ONLINE-001']],
];
$couponOnlineMatchedDetail = ['g_id' => 0, 'wc_order_no' => json_encode(['child-1' => ['out_no' => 'ONLINE-001']])];
$couponOnlineUnmatchedDetail = ['g_id' => 0, 'wc_order_no' => json_encode(['child-2' => ['out_no' => 'ONLINE-002']])];
$couponOfflineDetail = ['g_id' => 1001, 'wc_order_no' => ''];
$fdOnlineDetails = [
    ['sod_id' => 11, 'wc_order_no' => json_encode(['child-1' => ['out_no' => 'ONLINE-001']])],
    ['sod_id' => 12, 'wc_order_no' => json_encode(['child-2' => ['out_no' => 'ONLINE-002']])],
];

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
    'coupon runtime matches wc out number independently' => strpos($couponRuntime, 'couponDetailMatchesOnlineGoods') !== false
        && strpos($couponRuntime, 'couponDetailIsEligible') !== false,
    'coupon configured online goods is eligible' => $couponMatchProbe->eligible($couponOnlineMatchedDetail, $couponScope) === true,
    'coupon unconfigured online goods is not eligible' => $couponMatchProbe->eligible($couponOnlineUnmatchedDetail, $couponScope) === false,
    'coupon all-goods mode still includes offline goods' => $couponMatchProbe->eligible($couponOfflineDetail, $couponScope) === true,
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
    'fd runtime accepts any configured online goods match' => $fdMatchProbe->matches(
        $fdOnlineDetails,
        [['source_no' => 'ONLINE-001'], ['source_no' => 'ONLINE-003']]
    ) === true,
    'fd runtime records only matched online detail ids' => $fdMatchProbe->matchedSodIds(
        $fdOnlineDetails,
        [['source_no' => 'ONLINE-001']]
    ) === [11],
    'fd discount applies only to matched online details' => array_column($fdMatchProbe->eligibleDiscountDetails([
        ['sod_id' => 11, 'is_gift' => 2, 'total_sod_price' => 30],
        ['sod_id' => 12, 'is_gift' => 2, 'total_sod_price' => 20],
        ['sod_id' => 13, 'is_gift' => 2, 'total_sod_price' => 60],
    ], [11]), 'sod_id') === [11],
    'fd matcher returns false when no online goods match' => $fdMatchProbe->matches(
        $fdOnlineDetails,
        [['source_no' => 'ONLINE-003']]
    ) === false,
    'fd matcher returns false for empty online goods configuration' => $fdMatchProbe->matches(
        $fdOnlineDetails,
        []
    ) === false,
    'fd amount rules keep online goods independent' => strpos($fdRuntime, 'fdOnlineGoodsMatch') !== false
        && strpos($fdRuntime, "['fd_id' => \$this->fd['fd_id'], 'goods_source' => 1]") !== false
        && strpos($fdRuntime, '线上商品不适用当前满减活动') === false
        && strpos($fdRuntime, "\$this->order['total_quantity'] >= \$value['condition_value']") !== false
        && strpos($fdRuntime, 'getFdDiscountEligibleDetails') !== false
        && strpos($fdRuntime, "if (\$this->fd['condition_type'] != 3) {\n            \$onlineDetails") === false,
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
