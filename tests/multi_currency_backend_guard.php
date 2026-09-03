<?php

$root = dirname(__DIR__);
require_once $root . '/app/AppFactory/Kernel/Support/Currency/CurrencyPriceSupport.php';
require_once $root . '/app/AppFactory/Kernel/Traits/Goods/GoodsTrait.php';

use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;

class GoodsUpdateResultIdProbe
{
    use GoodsTrait;

    public function resolve($result, array $update, array $where)
    {
        return $this->resolveUpdatedGoodsId($result, $update, $where);
    }
}

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
$goodsTrait = $read('app/AppFactory/Kernel/Traits/Goods/GoodsTrait.php');
$machineGoodsController = $read('app/management/controller/machine/MachineGoods.php');
$machineChannelController = $read('app/management/controller/machine/MachineChannel.php');
$machineGoodsClient = $read('app/AppFactory/Management/Machine/MachineGoodsClient.php');
$currencyPriceService = $read('app/AppFactory/Kernel/Service/Currency/MachineCurrencyPriceService.php');
$apifox = $read('文档说明/多货币商品价格体系.apifox.openapi.json');
$apifoxDoc = json_decode($apifox, true);

$apifoxPathCount = is_array($apifoxDoc) && isset($apifoxDoc['paths']) ? count($apifoxDoc['paths']) : 0;
$apifoxBodyCount = 0;
$apifoxManagementCount = 0;
$apifoxManagementTokenCount = 0;
$apifoxMachineCount = 0;
$apifoxMachineMacCount = 0;
if (is_array($apifoxDoc) && isset($apifoxDoc['paths'])) {
    foreach ($apifoxDoc['paths'] as $path => $pathItem) {
        if (isset($pathItem['post']['requestBody'])) $apifoxBodyCount++;
        $operationParameters = isset($pathItem['post']['parameters']) ? $pathItem['post']['parameters'] : [];
        $pathParameters = isset($pathItem['parameters']) ? $pathItem['parameters'] : [];
        if (strpos($path, '/management/') === 0) {
            $apifoxManagementCount++;
            foreach ($operationParameters as $parameter) {
                if (isset($parameter['name'], $parameter['in'], $parameter['example'])
                    && $parameter['name'] === 'token'
                    && $parameter['in'] === 'header'
                    && $parameter['example'] === '{{token}}'
                    && isset($parameter['schema']['default'])
                    && $parameter['schema']['default'] === '{{token}}') {
                    $apifoxManagementTokenCount++;
                }
            }
        }
        if (strpos($path, '/machine/') === 0) {
            $apifoxMachineCount++;
            foreach ($pathParameters as $parameter) {
                if (isset($parameter['$ref']) && $parameter['$ref'] === '#/components/parameters/MachineMacHeader') {
                    $apifoxMachineMacCount++;
                }
            }
        }
    }
}

$checks = [
    'four currency tables exist' => count(array_filter([
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `currency_info`') !== false,
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `goods_currency_price`') !== false,
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `machine_goods_currency_price`') !== false,
        strpos($sql, 'CREATE TABLE IF NOT EXISTS `machine_channel_currency_price`') !== false,
    ])) === 4,
    'no automatic unlock price overwrite' => strpos($channel, '解锁只修改锁定状态') !== false
        && strpos($channel, "getGoodsFind(['g_id' => \$value['g_id']],'cost_price,market_price,retail_price')") === false,
    'multi currency sync entrypoints exist' => strpos($machineGoodsController, 'public function synchronizationGoods') !== false
        && strpos($machineChannelController, 'public function synchronizationMachineGoodsPrice') !== false
        && strpos($machineGoodsClient, 'public function synchronizationGoodsPrice') !== false
        && strpos($channel, 'public function synchronizationMachineGoodsPrice') !== false
        && strpos($currencyPriceService, 'public function syncMachineGoodsCurrencies') !== false
        && strpos($currencyPriceService, 'public function syncMachineChannelCurrencies') !== false
        && strpos($currencyPriceService, 'public function syncMachineChannels') !== false
        && strpos($currencyPriceService, 'protected function assertOrdinaryChannels') !== false,
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
    'multi currency sync stays atomic' => strpos($currencyPriceService, 'public function syncMachineGoodsCurrencies') !== false
        && substr_count($currencyPriceService, 'return $manageTransaction ? Db::transaction($save) : $save();') >= 2
        && strpos($currencyPriceService, "'currency_results' => \$currencyResults") !== false,
    'currency code normalizes to uppercase' => CurrencyPriceSupport::normalizeCurrencyCode(' hkd ') === 'HKD',
    'currency code arrays normalize and deduplicate' => CurrencyPriceSupport::normalizeCurrencyCodes([' cny ', 'HKD', 'CNY']) === ['CNY', 'HKD'],
    'ids are positive unique' => CurrencyPriceSupport::normalizeIds('3,2,3,0,-1') === [3, 2],
    'decimal is normalized to three places' => CurrencyPriceSupport::normalizePrice('12.3') === '12.300',
    'apifox document is valid json' => is_array($apifoxDoc) && json_last_error() === JSON_ERROR_NONE,
    'apifox documents every multi currency endpoint body' => $apifoxPathCount === 20
        && $apifoxBodyCount === $apifoxPathCount,
    'apifox management headers explicitly use token variable' => $apifoxManagementCount === 17
        && $apifoxManagementTokenCount === $apifoxManagementCount,
    'apifox machine headers use mac variable' => $apifoxMachineCount === 3
        && $apifoxMachineMacCount === $apifoxMachineCount,
    'apifox core goods body documents actual required fields' => isset($apifoxDoc['components']['schemas']['GoodsCreateRequest']['allOf'][1]['required'])
        && $apifoxDoc['components']['schemas']['GoodsCreateRequest']['allOf'][1]['required']
            === ['g_name', 'sku', 'release_time', 'length', 'width', 'height', 'currency_prices'],
    'apifox core prices use currency keyed object' => isset($apifoxDoc['components']['schemas']['CoreCurrencyPriceMap']['additionalProperties']['$ref'])
        && $apifoxDoc['components']['schemas']['CoreCurrencyPriceMap']['additionalProperties']['$ref']
            === '#/components/schemas/CurrencyPriceTripleInput',
    'apifox machine goods currency code is optional' => isset($apifoxDoc['components']['schemas']['MachineGoodsPriceSaveRequest']['required'])
        && !in_array('currency_code', $apifoxDoc['components']['schemas']['MachineGoodsPriceSaveRequest']['required'], true),
    'apifox goods detail requires g_id' => isset($apifoxDoc['components']['schemas']['GoodsFindRequest']['required'])
        && $apifoxDoc['components']['schemas']['GoodsFindRequest']['required'] === ['g_id'],
    'apifox currency list only documents supported status filter' => isset($apifoxDoc['components']['schemas']['CurrencyListRequest']['properties']['status'])
        && !isset($apifoxDoc['components']['schemas']['CurrencyListRequest']['properties']['currency_code']),
];

$invalidCodeRejected = false;
try {
    CurrencyPriceSupport::normalizeCurrencyCode('RMB1');
} catch (InvalidArgumentException $e) {
    $invalidCodeRejected = true;
}
$checks['invalid currency code is rejected'] = $invalidCodeRejected;

$nonArrayCodesRejected = false;
try {
    CurrencyPriceSupport::normalizeCurrencyCodes('CNY,HKD');
} catch (InvalidArgumentException $e) {
    $nonArrayCodesRejected = true;
}
$checks['non array currency codes are rejected'] = $nonArrayCodesRejected;

$goodsUpdateProbe = new GoodsUpdateResultIdProbe();
$checks['goods update recovers g_id from where'] = $goodsUpdateProbe->resolve([], ['g_name' => '新名称'], ['g_id' => 1001]) === 1001;
$checks['goods update keeps result g_id priority'] = $goodsUpdateProbe->resolve(['g_id' => 1002], ['g_id' => 1003], ['g_id' => 1004]) === 1002;
$checks['goods update supports condition-array g_id'] = $goodsUpdateProbe->resolve([], ['bar_code' => '690000000001'], [['g_id', '=', 1005]]) === 1005;
$checks['goods update rejects missing g_id'] = $goodsUpdateProbe->resolve([], ['g_name' => '新名称'], []) === 0;
$checks['goods update checks null before toArray'] = strpos($goodsTrait, 'if (!$newGoods)') !== false
    && strpos($goodsTrait, '$new = $newGoods->toArray();') !== false;
$checks['goods downstream updates use resolved g_id'] = substr_count($goodsTrait, "['g_id' => \$gId]") >= 4
    && strpos($goodsTrait, "['g_id' => \$result['g_id']]") === false;

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}
exit($failed ? 1 : 0);
