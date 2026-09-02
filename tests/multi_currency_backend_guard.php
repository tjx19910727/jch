<?php

$root = dirname(__DIR__);
require_once $root . '/app/AppFactory/Kernel/Support/Currency/CurrencyPriceSupport.php';

use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;

$read = function ($path) use ($root) {
    $content = file_get_contents($root . '/' . $path);
    if ($content === false) throw new RuntimeException('cannot read ' . $path);
    return $content;
};

$sql = $read('文档说明/多货币商品价格体系MySQL5.6数据库变更.sql');
$channel = $read('app/AppFactory/Management/Machine/MachineChannelClient.php');
$config = $read('app/AppFactory/Management/Machine/MachineConfigClient.php');
$order = $read('app/AppFactory/Kernel/Traits/SaleOrders/SaleOrdersTrait.php');
$excel = $read('app/AppFactory/Kernel/Support/Excel.php');
$receive = $read('app/AppFactory/Machine/Receive/ApiClient.php');
$thirdParty = $read('app/AppFactory/Kernel/Service/Api/ThirdPartyProductSnapshotService.php');
$goodsController = $read('app/management/controller/goods/Goods.php');

$checks = [
    'four currency tables exist' => count(array_filter([
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `currency_info`') !== false,
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `goods_currency_price`') !== false,
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `machine_goods_currency_price`') !== false,
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `machine_channel_currency_price`') !== false,
    ])) === 4,
    'no automatic unlock price overwrite' => strpos($channel, '解锁只修改锁定状态') !== false
        && strpos($channel, "getGoodsFind(['g_id' => \$value['g_id']],'cost_price,market_price,retail_price')") === false,
    'ordinary channel price uses dedicated API' => strpos($channel, 'public function saveCurrencyPrice') !== false
        && strpos($channel, 'public function synchronizationMachineGoodsPrice') !== false,
    'generic machine config cannot bypass switch' => substr_count($config, '设备币种只能通过币种切换接口修改') >= 2,
    'orders contain currency snapshot guard' => strpos($order, 'appendSaleOrderCurrencySnapshot') !== false
        && strpos($order, '订单币种版本已过期') !== false,
    'excel import is header based' => strpos($excel, 'public static function importExcelByHeader') !== false,
    'device backend reservation exists' => strpos($receive, 'public function currencySnapshot') !== false
        && strpos($receive, 'public function reportCurrencySwitchState') !== false
        && strpos($receive, 'public function updateMachineGoodsCurrencyPrice') !== false,
    'external V2 snapshot contract is unchanged' => strpos($thirdParty, 'GoodsCurrencyPriceService') === false
        && strpos($thirdParty, "['currency_prices']") === false,
    'cost price permission guards import and edit' => strpos($goodsController, 'containsCurrencyCostPrice') !== false
        && strpos($goodsController, 'importExcelV2($postData, $this->hasCostPriceAuth())') !== false,
    'currency code normalizes to uppercase' => CurrencyPriceSupport::normalizeCurrencyCode(' hkd ') === 'HKD',
    'ids are positive unique' => CurrencyPriceSupport::normalizeIds('3,2,3,0,-1') === [3, 2],
    'decimal is normalized to three places' => CurrencyPriceSupport::normalizePrice('12.3') === '12.300',
];

$invalidCodeRejected = false;
try {
    CurrencyPriceSupport::normalizeCurrencyCode('RMB1');
} catch (InvalidArgumentException $e) {
    $invalidCodeRejected = true;
}
$checks['invalid currency code is rejected'] = $invalidCodeRejected;

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}
exit($failed ? 1 : 0);
