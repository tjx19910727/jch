<?php

$resolver = file_get_contents(__DIR__ . '/../app/AppFactory/Kernel/Service/Payment/CartPayeeStrategyResolver.php');
$receive = file_get_contents(__DIR__ . '/../app/AppFactory/Machine/Receive/ApiClient.php');
$payment = file_get_contents(__DIR__ . '/../app/AppFactory/Pay/SaleOrders/PaymentClient.php');
$machineGoods = file_get_contents(__DIR__ . '/../app/AppFactory/Management/Machine/MachineGoodsClient.php');
$machineGoodsController = file_get_contents(__DIR__ . '/../app/management/controller/machine/MachineGoods.php');
$machineGoodsValidator = file_get_contents(__DIR__ . '/../app/management/validate/Machine/VMachineGoods.php');

$checks = [
    'multiple explicit goods strategies have priority' => strpos($resolver, 'getGoodsStrategies(') !== false
        && strpos($resolver, "'goods_explicit'") !== false,
    'resolver accepts thinkphp machine model' => strpos($resolver, 'public static function resolve($machine, array $cartList, $payType = 0)') !== false
        && strpos($resolver, "method_exists(\$machine, 'toArray')") !== false
        && strpos($resolver, "'machine_invalid'") !== false,
    'cart strategies use set intersection' => strpos($resolver, 'array_intersect($candidateIds, $itemCandidates)') !== false,
    'legacy organization strategy remains supported' => strpos($resolver, "where('sm.ao_id', \$goodsAoId)") !== false,
    'legacy online offline strategy scope remains supported' => strpos($resolver, 'SubCarMixPolicy::ONLINE_SP_IDS_FIELD') !== false
        && strpos($resolver, 'SubCarMixPolicy::OFFLINE_SP_IDS_FIELD') !== false,
    'empty offline strategy scope falls back to machine organization strategy' => strpos($resolver, '$offlineLegacyIds = $configuredOfflineIds ?: null;') !== false
        && strpos($resolver, '$legacyGoodsAoId = $offlineFallbackToMachineOrg ? 0 : $goodsAoId;') !== false,
    'non-empty offline strategy scope remains restrictive' => strpos($resolver, "if (is_array(\$allowedSpIds)) \$query->where('sp.sp_id', 'in', \$allowedSpIds);") !== false,
    'empty online strategy scope falls back to jch organization strategy' => strpos($resolver, '$onlineLegacyIds = $configuredOnlineIds ?: null;') !== false
        && strpos($resolver, 'self::JCH_ORG_AO_ID') !== false
        && strpos($resolver, '$onlineLegacyIds,') !== false,
    'online organization fallback is strict' => strpos($resolver, 'if (!$fallbackToMachineOrg) return [];') !== false,
    'mixed strategy cart is rejected' => strpos($resolver, "'strategy_conflict'") !== false,
    'wechat and alipay variant types remain compatible' => strpos($resolver, '[11, 12]') !== false
        && strpos($resolver, '[21, 22]') !== false,
    'subCar revalidates and snapshots selected strategy' => strpos($receive, 'CartPayeeStrategyResolver::resolve(') !== false
        && strpos($receive, "'effective_sp_id'") !== false
        && strpos($receive, "\$updateOrder['sp_id']") !== false,
    'payment uses order strategy snapshot' => strpos($payment, "\$this->order['sp_id']") !== false
        && strpos($payment, 'getStrategyPayeeContentDirect') !== false,
    'payment does not reject empty strategy scope before snapshot' => strpos($payment, 'if (!$subCarMixSpIds) $subCarMixSpIds = null;') !== false
        && strpos($payment, 'VOrderPay.subcar_mix_payee_empty') === false,
    'legacy payment empty scope uses source organization' => strpos($payment, '$goodsSource === SubCarMixPolicy::SOURCE_ONLINE') !== false
        && strpos($payment, '? 17') !== false
        && strpos($payment, "intval(\$this->machine['ao_id'] ?? 0)") !== false,
    'management syncs and validates multiple strategies' => strpos($machineGoods, 'validatePayeeStrategies') !== false
        && strpos($machineGoods, 'syncPayeeStrategies') !== false
        && strpos($machineGoods, "machine_goods_payee_strategy") !== false
        && strpos($machineGoods, '收款策略与商品所属组织不匹配') !== false,
    'machine goods detail uses aliased list query' => strpos($machineGoodsController, 'getMgFind($where, $this->field)') !== false
        && strpos($machineGoods, 'public function getMgFind($where, $field = "*")') !== false
        && strpos($machineGoods, 'getMachineGoodsList($where, 0, $field)') !== false,
    'machine goods table does not store strategy id' => strpos($resolver, "field('mg_id,ao_id,g_id,g_name,sp_id')") === false
        && strpos($machineGoods, "\$postData['sp_id'] =") === false
        && strpos($machineGoodsController, 'a.sp_id') === false,
    'strategy only update does not write or notify machine goods' => strpos($machineGoods, '$machineGoodsFields = array_diff(array_keys($postData), [\'mg_id\', \'lang\'])') !== false
        && strpos($machineGoods, '$result = $hasMachineGoodsUpdate ? $this->updateMachineGoods($postData) : 0') !== false
        && strpos($machineGoods, 'if ($result) $this->afterMgUpdate($mgId)') !== false,
    'batch endpoint is exposed and validated' => strpos($machineGoodsController, 'public function updatePayeeStrategiesBatch()') !== false
        && strpos($machineGoodsValidator, '"updatePayeeStrategiesBatch" => ["mg_ids","sp_ids"]') !== false,
    'organization strategy endpoint uses goods organization and is read only' => strpos($machineGoodsController, 'public function getOrganizationPayeeStrategies()') !== false
        && strpos($machineGoodsValidator, '"getOrganizationPayeeStrategies" => ["mg_id"]') !== false
        && strpos($machineGoods, "where(['ao_id' => \$aoId, 'status' => 1])") !== false
        && strpos($machineGoods, "'strategies' => array_values(\$strategies)") !== false,
    'batch update uses transaction and full replacement' => strpos($machineGoods, 'public function updatePayeeStrategiesBatch($postData)') !== false
        && strpos($machineGoods, '$this->startTrans()') !== false
        && strpos($machineGoods, '$this->syncPayeeStrategies($mgId, $spIds)') !== false
        && strpos($machineGoods, "'updated_count' => count(\$goodsList)") !== false,
    'batch update checks machine permission' => strpos($machineGoods, 'resolvePermittedMachineIdsForBatch') !== false
        && strpos($machineGoods, '无权配置部分设备商品') !== false,
    'batch update has bounded request size' => strpos($machineGoods, '单次最多配置500个设备商品') !== false
        && strpos($machineGoods, '单个设备商品最多配置50个收款策略') !== false,
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    if (!$passed) exit(1);
}

echo "machine goods pay strategy guard passed\n";
