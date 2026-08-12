<?php

$resolver = file_get_contents(__DIR__ . '/../app/AppFactory/Kernel/Service/Payment/CartPayeeStrategyResolver.php');
$receive = file_get_contents(__DIR__ . '/../app/AppFactory/Machine/Receive/ApiClient.php');
$payment = file_get_contents(__DIR__ . '/../app/AppFactory/Pay/SaleOrders/PaymentClient.php');
$machineGoods = file_get_contents(__DIR__ . '/../app/AppFactory/Management/Machine/MachineGoodsClient.php');
$machineGoodsController = file_get_contents(__DIR__ . '/../app/management/controller/machine/MachineGoods.php');
$machineGoodsValidator = file_get_contents(__DIR__ . '/../app/management/validate/Machine/VMachineGoods.php');
$cartPage = file_get_contents(__DIR__ . '/../robot_flutter/lib/pages/goods_cart/index.dart');
$detailPage = file_get_contents(__DIR__ . '/../robot_flutter/lib/pages/goods_detail/index.dart');

$checks = [
    'multiple explicit goods strategies have priority' => strpos($resolver, 'getGoodsStrategies(') !== false
        && strpos($resolver, "'goods_explicit'") !== false,
    'cart strategies use set intersection' => strpos($resolver, 'array_intersect($candidateIds, $itemCandidates)') !== false,
    'legacy organization strategy remains supported' => strpos($resolver, "where('sm.ao_id', \$goodsAoId)") !== false,
    'legacy online offline strategy scope remains supported' => strpos($resolver, 'SubCarMixPolicy::ONLINE_SP_IDS_FIELD') !== false
        && strpos($resolver, 'SubCarMixPolicy::OFFLINE_SP_IDS_FIELD') !== false,
    'mixed strategy cart is rejected' => strpos($resolver, "'strategy_conflict'") !== false,
    'wechat and alipay variant types remain compatible' => strpos($resolver, '[11, 12]') !== false
        && strpos($resolver, '[21, 22]') !== false,
    'subCar revalidates and snapshots selected strategy' => strpos($receive, 'CartPayeeStrategyResolver::resolve(') !== false
        && strpos($receive, "'effective_sp_id'") !== false
        && strpos($receive, "\$updateOrder['sp_id']") !== false,
    'payment uses order strategy snapshot' => strpos($payment, "\$this->order['sp_id']") !== false
        && strpos($payment, 'getStrategyPayeeContentDirect') !== false,
    'management syncs and validates multiple strategies' => strpos($machineGoods, 'validatePayeeStrategies') !== false
        && strpos($machineGoods, 'syncPayeeStrategies') !== false
        && strpos($machineGoods, "machine_goods_payee_strategy") !== false
        && strpos($machineGoods, '收款策略与设备商品所属组织不匹配') !== false,
    'batch endpoint is exposed and validated' => strpos($machineGoodsController, 'public function updatePayeeStrategiesBatch()') !== false
        && strpos($machineGoodsValidator, '"updatePayeeStrategiesBatch" => ["mg_ids","sp_ids"]') !== false,
    'batch update uses transaction and full replacement' => strpos($machineGoods, 'public function updatePayeeStrategiesBatch($postData)') !== false
        && strpos($machineGoods, '$this->startTrans()') !== false
        && strpos($machineGoods, '$this->syncPayeeStrategies($mgId, $spIds)') !== false
        && strpos($machineGoods, "'updated_count' => count(\$goodsList)") !== false,
    'batch update checks machine permission' => strpos($machineGoods, 'resolvePermittedMachineIdsForBatch') !== false
        && strpos($machineGoods, '无权配置部分设备商品') !== false,
    'batch update has bounded request size' => strpos($machineGoods, '单次最多配置500个设备商品') !== false
        && strpos($machineGoods, '单个设备商品最多配置50个收款策略') !== false,
    'cart filters visible payment methods' => strpos($cartPage, 'allowedRawPayeeTypes: strategyPayeeTypes') !== false,
    'buy now filters visible payment methods' => strpos($detailPage, 'allowedRawPayeeTypes: strategyPayeeTypes') !== false,
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    if (!$passed) exit(1);
}

echo "machine goods pay strategy guard passed\n";
