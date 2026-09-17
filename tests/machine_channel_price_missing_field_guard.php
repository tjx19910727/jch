<?php

// 守卫：货道改价在「缺币种价事实行」时，不允许因为只提交了部分三价而失败。
//
// 背景（生产实测）：普通编辑 machine.machine_channel/update 与批量改价 batchUpdate 只提交
// 用户改动的价格字段，而 MachineCurrencyPriceService 要求“首次保存必须完整三价”；
// 货道缺 machine_channel_currency_price 事实行时既无入参也无库值可补，
// 直接抛 InvalidArgumentException('缺少价格字段：cost_price')，被 catch 包成 3301，运营无法自助恢复。
//
// 修复：缺事实行且保存币种=设备当前币种时，用 machine_channel 活跃快照兜底未提交字段；
//      该内部文案统一转成可操作提示。

$root = dirname(__DIR__);
require_once $root . '/app/AppFactory/Kernel/Support/Currency/CurrencyPriceSupport.php';
require_once $root . '/app/AppFactory/Kernel/Service/Currency/MachineCurrencyPriceService.php';

$serviceClass = 'app\AppFactory\Kernel\Service\Currency\MachineCurrencyPriceService';
$supportClass = 'app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport';
$clientSource = file_get_contents($root . '/app/AppFactory/Management/Machine/MachineChannelClient.php');
$serviceSource = file_get_contents($root . '/app/AppFactory/Kernel/Service/Currency/MachineCurrencyPriceService.php');
if ($clientSource === false || $serviceSource === false) {
    throw new RuntimeException('cannot read machine channel sources');
}

function guardBody($source, $methodName)
{
    $needle = 'function ' . $methodName . '(';
    $start = strpos($source, $needle);
    if ($start === false) return '';
    $boundaries = [];
    foreach (["\n    public function ", "\n    protected function ", "\n    private function ",
                 "\n    public static function ", "\n    protected static function "] as $marker) {
        $pos = strpos($source, $marker, $start + strlen($needle));
        if ($pos !== false) $boundaries[] = $pos;
    }
    if (!$boundaries) return substr($source, $start);
    return substr($source, $start, min($boundaries) - $start);
}

$merge = new ReflectionMethod($serviceClass, 'mergeSnapshotPriceFallback');
$merge->setAccessible(true);
$service = (new ReflectionClass($serviceClass))->newInstanceWithoutConstructor();
$mergeFallback = function (array $input, $mc, $currency, array $config, $existing) use ($merge, $service) {
    return $merge->invoke($service, $input, $mc, $currency, $config, $existing);
};

$config = ['currency_code' => 'CNY'];
// 生产缺价货道 mc_id=32317 的真实快照
$snapshot = ['cost_price' => '8.500', 'market_price' => '23.000', 'retail_price' => '23.000'];

$merged = $mergeFallback(['retail_price' => '25.000'], $snapshot, 'CNY', $config, null);
$normalizeError = '';
$normalized = null;
try {
    $normalized = call_user_func([$supportClass, 'normalizePriceRow'], $merged, []);
} catch (\InvalidArgumentException $e) {
    $normalizeError = $e->getMessage();
}

// 对照组：不补齐时必须失败（证明该守卫针对的问题真实存在）
$legacyError = '';
try {
    call_user_func([$supportClass, 'normalizePriceRow'], ['retail_price' => '25.000'], []);
} catch (\InvalidArgumentException $e) {
    $legacyError = $e->getMessage();
}

$explicit = $mergeFallback(
    ['cost_price' => '5.000', 'market_price' => '6.000', 'retail_price' => '7.000'],
    $snapshot,
    'CNY',
    $config,
    null
);
$blank = $mergeFallback(['cost_price' => '', 'retail_price' => '25.000'], $snapshot, 'CNY', $config, null);
$withFact = $mergeFallback(['retail_price' => '25.000'], $snapshot, 'CNY', $config, ['mccp_id' => 1, 'cost_price' => '9.900']);
$otherCurrency = $mergeFallback(['retail_price' => '25.000'], $snapshot, 'HKD', $config, null);
$invalidMerged = $mergeFallback(
    ['retail_price' => '25.000'],
    ['cost_price' => '-1.000', 'market_price' => '23.000', 'retail_price' => '23.000'],
    'CNY',
    $config,
    null
);
$emptyMc = $mergeFallback(['retail_price' => '25.000'], null, 'CNY', $config, null);
$formatter = guardBody($clientSource, 'formatChannelPriceFailure');

$checks = [
    '缺事实行 + 只传零售价 → 用快照补齐成本价/市场价' => $merged['cost_price'] === '8.500'
        && $merged['market_price'] === '23.000'
        && $merged['retail_price'] === '25.000',
    '补齐结果通过服务层三价校验（不再抛缺少价格字段）' => $normalizeError === ''
        && $normalized['cost_price'] === '8.500'
        && $normalized['market_price'] === '23.000'
        && $normalized['retail_price'] === '25.000',
    '对照组：不补齐时仍抛“缺少价格字段：cost_price”（修复必要性）' => $legacyError === '缺少价格字段：cost_price',
    '入参整体优先于快照（用户显式填的三价不被覆盖）' => $explicit['cost_price'] === '5.000'
        && $explicit['market_price'] === '6.000'
        && $explicit['retail_price'] === '7.000',
    '入参空串视为未提供 → 用快照补齐（前端清空成本价也不再报错）' => $blank['cost_price'] === '8.500'
        && $blank['retail_price'] === '25.000',
    '已有事实行时不做接管（事实行是该币种价格权威来源）' => count($withFact) === 1
        && $withFact['retail_price'] === '25.000',
    '非设备当前币种不兜底（快照只保存活跃币种价）' => count($otherCurrency) === 1,
    '快照值非法（-1 占位）不兜底，保留原提示' => !isset($invalidMerged['cost_price'])
        && $invalidMerged['market_price'] === '23.000'
        && $invalidMerged['retail_price'] === '25.000',
    '货道不存在（mc 为空）时不兜底' => count($emptyMc) === 1,
    '兜底只在服务层实现（client 不得自行拼三价绕过校验）' => strpos($clientSource, 'mergeSnapshotPriceFallback') === false,
    '服务层单条保存走快照兜底' => strpos(
        guardBody($serviceSource, 'saveMachineChannelPrice'),
        'mergeSnapshotPriceFallback($priceInput, $mc, $currencyCode, $config, $existing)'
    ) !== false,
    '服务层批量保存走快照兜底' => strpos(
        guardBody($serviceSource, 'saveMachineChannelPrices'),
        'mergeSnapshotPriceFallback($priceInput, $mc, $currencyCode, $config, $existing)'
    ) !== false,
    '服务层仍保留全 0 三价拦截（不放松首次保存保护）' => strpos($serviceSource, 'CurrencyPriceSupport::isZeroPrice($price)') !== false
        && strpos($serviceSource, '不能直接保存全为0的三价') !== false,
    '服务层批量改价仍只递增一次币种版本' => substr_count(guardBody($serviceSource, 'saveMachineChannelPrices'), 'bumpCurrencyVersion(') === 1,
    'updateMc 失败文案改为可操作提示' => strpos(
        guardBody($clientSource, 'updateMc'),
        'rTryCatch($this->formatChannelPriceFailure($e->getMessage()))'
    ) !== false,
    'batchUpdate 失败文案改为可操作提示且保留异常日志' => strpos(
        guardBody($clientSource, 'batchUpdateMc'),
        'r(100, $this->formatChannelPriceFailure($e->getMessage()))'
    ) !== false
        && strpos(guardBody($clientSource, 'batchUpdateMc'), 'actionException($e, 1);') !== false,
    '文案助手把“缺少价格字段”转成下一步动作' => $formatter !== ''
        && strpos($formatter, "strpos(\$message, '缺少价格字段') === 0") !== false
        && strpos($formatter, '请补全成本价/市场价/零售价后提交') !== false
        && strpos($formatter, '货道币种价格同步') !== false,
    '文案助手不改动其它业务异常（如未绑定商品）' => $formatter !== ''
        && strpos($formatter, 'return $message;') !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);

