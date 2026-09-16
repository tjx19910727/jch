<?php

// 守卫：management/machine.machine_channel/batchUpdate 的 mc_ids 参数格式
// 历史：client 一直按逗号字符串实现（2026-03-13 explode），校验层 2026-09-03 被改成 require|array，
//      导致字符串被拦（300 货道ID必须是数组）、数组又被 explode 打死，两种传法都不可用。
// 现统一为“数组或英文逗号分隔字符串都接受”，且归一化刻意放在管理端 client 内（不放共享 trait，
// 因为设备端/MQ 侧的 MqClient 也 use 了 MachineChannelTrait）。

$root = dirname(__DIR__);
$validator = file_get_contents($root . '/app/management/validate/Machine/VMachineChannel.php');
$client = file_get_contents($root . '/app/AppFactory/Management/Machine/MachineChannelClient.php');
$trait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Machine/MachineChannelTrait.php');
$controller = file_get_contents($root . '/app/management/controller/machine/MachineChannel.php');
$service = file_get_contents($root . '/app/AppFactory/Kernel/Service/Currency/MachineCurrencyPriceService.php');
if ($validator === false || $client === false || $trait === false || $controller === false || $service === false) {
    throw new RuntimeException('cannot read machine channel files');
}

function guardBatchUpdateBody($source, $methodName)
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

$checkMcIds = guardBatchUpdateBody($validator, 'checkMcIds');
$normalize = guardBatchUpdateBody($client, 'normalizeBatchMcIds');
$batchUpdateMc = guardBatchUpdateBody($client, 'batchUpdateMc');
$batchRestoreMc = guardBatchUpdateBody($client, 'batchRestoreMc');
$batchUpdate = guardBatchUpdateBody($controller, 'batchUpdate');
$saveChannelPrices = guardBatchUpdateBody($service, 'saveMachineChannelPrices');

$checks = [
    '校验规则不再强制数组' => strpos($validator, '"mc_ids" => "require|checkMcIds"') !== false
        && strpos($validator, '"mc_ids" => "require|array"') === false
        && strpos($validator, '"mc_ids.array"') === false,
    '校验层 checkMcIds 兼容数组/标量且拦纯非法值' => $checkMcIds !== ''
        && strpos($checkMcIds, 'is_array($value)') !== false
        && strpos($checkMcIds, 'is_scalar($value)') !== false
        && strpos($checkMcIds, "explode(',', (string)\$value)") !== false
        && strpos($checkMcIds, 'return $valid > 0;') !== false,
    '归一化助手位于管理端 client 内（兼容数组与逗号字符串）' => $normalize !== ''
        && strpos($normalize, 'if (!is_array($mcIds))') !== false
        && strpos($normalize, "explode(',', (string)\$mcIds)") !== false
        && strpos($normalize, '$result[$mcId] = $mcId;') !== false,
    '归一化不放进共享 trait（避免影响设备端/MQ 侧加载的文件）'
        => strpos($trait, 'normalizeBatchMcIds') === false,
    'client 不再对数组 explode' => $batchUpdateMc !== ''
        && strpos($batchUpdateMc, '$this->normalizeBatchMcIds($postData[\'mc_ids\'] ?? \'\')') !== false
        && strpos($batchUpdateMc, 'explode(",",$mc_ids)') === false
        && strpos($batchUpdateMc, 'explode(",", $mc_ids)') === false,
    '批量还原同样走归一化（同一 updateAll 场景）' => $batchRestoreMc !== ''
        && strpos($batchRestoreMc, '$this->normalizeBatchMcIds($postData[\'mc_ids\'] ?? [])') !== false,
    'client 保持非法/空值提示' => strpos($batchUpdateMc, 'VMachineChannel.mc_id_require') !== false,
    '零售价不再被直接拦截，改为收集 priceInput' => strpos($batchUpdateMc, "isset(\$postData['retail_price']) && trim((string)\$postData['retail_price']) !== ''") !== false
        && strpos($batchUpdateMc, "\$priceInput['retail_price'] = \$postData['retail_price']") !== false
        && strpos($batchUpdateMc, '普通货道售价请使用目标币种改价接口') === false,
    '零售价走币种价格服务并交出事务控制' => strpos($batchUpdateMc, 'saveMachineChannelPrices(') !== false
        && preg_match('/saveMachineChannelPrices\([^;]*?false\s*\);/s', $batchUpdateMc) === 1,
    '批改整体包在事务内且失败整批回滚' => strpos($batchUpdateMc, '$this->startTrans();') !== false
        && strpos($batchUpdateMc, '$this->commitTrans();') !== false
        && strpos($batchUpdateMc, '$this->rollbackTrans();') !== false,
    '提交后只通知一次币种快照' => strpos($batchUpdateMc, 'if ($priceResult) {') !== false
        && strpos($batchUpdateMc, '$this->notifyCurrencySnapshot($priceResult);') !== false,
    '服务层批量改价写事实表 + 当前币种回写快照' => $saveChannelPrices !== ''
        && strpos($saveChannelPrices, 'upsertMachineChannelPrice(') !== false
        && strpos($saveChannelPrices, "Db::name('machine_channel')->where('mc_id', \$mcId)->update(\$price);") !== false,
    '服务层整批只递增一次版本' => $saveChannelPrices !== ''
        && substr_count($saveChannelPrices, 'bumpCurrencyVersion(') === 1
        && strpos($saveChannelPrices, '$snapshotChanged ? $this->bumpCurrencyVersion($mId) : $config[\'currency_version\']') !== false,
    '服务层沿用普通单商品货道与三价校验' => $saveChannelPrices !== ''
        && strpos($saveChannelPrices, 'assertOrdinaryChannels(') !== false
        && strpos($saveChannelPrices, 'normalizePriceRow($priceInput, $existing ?: [])') !== false
        && strpos($saveChannelPrices, 'CurrencyPriceSupport::isZeroPrice($price)') !== false,
    '控制器仍按 updateAll 场景校验后进入 client' => $batchUpdate !== ''
        && strpos($batchUpdate, '$this->validate($postData, $this->validatePath . \'.updateAll\');') !== false
        && strpos($batchUpdate, 'batchUpdateMc($postData, $where)') !== false,
    '批量场景显式要求 m_id（避免空 where 走到框架 find 兜底）' => strpos($validator, '"updateAll" => ["m_id", "mc_ids"]') !== false
        && strpos($batchUpdate, "\$this->getWhere(['m_id'=>\$postData['m_id']], false, [])") !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
