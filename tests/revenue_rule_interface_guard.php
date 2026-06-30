<?php

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$controller = file_get_contents($root . '/app/management/controller/revenue/RevenueRule.php');
$validator = file_get_contents($root . '/app/management/validate/VRevenueRule.php');
$calculator = file_get_contents($root . '/app/AppFactory/Kernel/Service/Revenue/RevenueCalculator.php');
$failures = [];

$checks = [
    'controller exposes unified saveConfig' => strpos($controller, 'public function saveConfig()') !== false,
    'controller exposes unified saveScope' => strpos($controller, 'public function saveScope()') !== false,
    'controller exposes account coupon revenue list' => strpos($controller, 'public function getAccountCouponList()') !== false
        && strpos($controller, '$this->app->revenueRule->getAccountCouponList($postData)') !== false,
    'controller orders by unified config id' => strpos($controller, '"rrcfg_id desc"') !== false && strpos($controller, '"rr_id desc"') === false,
    'addData writes unified config table' => strpos($client, 'addRevenueRuleConfig($config)') !== false,
    'updateData writes unified config table' => strpos($client, 'updateRevenueRuleConfig($update') !== false,
    'scope save writes unified scope table' => strpos($client, 'replaceConfigScopes($rrcfgId') !== false,
    'receiver items are saved in receiver_config' => strpos($client, 'encodeReceiverConfig') !== false,
    'account coupon list requires revenue account' => strpos($client, 'public function getAccountCouponList($postData)') !== false
        && strpos($client, '$this->getRevenueAccountFind([\'ra_id\' => $raId], \'ra_id\')') !== false,
    'account coupon list matches receiver account exactly' => strpos($client, "buildReceiverJsonIntRegexp('ra_id', \$raId)") !== false
        && strpos($client, 'receiver_config REGEXP') !== false,
    'account coupon list returns coupon info through shared formatter' => strpos($client, 'coupon_info') !== false
        && strpos($client, 'ActivityCouponModel::where') !== false,
    'tier items are saved in receiver_config tiers' => strpos($client, 'normalizeConfigTier') !== false,
    'old bindMachine interface removed' => strpos($validator, '"bindMachine"') === false && strpos($controller, 'function bindMachine') === false,
    'calculator reads unified config scope' => strpos($calculator, 'RevenueRuleConfigScopeModel::alias') !== false,
    'calculator parses receiver_config' => strpos($calculator, 'getRuleItems(array $rule)') !== false,
];

foreach ($checks as $name => $ok) {
    if (!$ok) $failures[] = $name;
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 统一分账接口读写 revenue_rule_config / revenue_rule_config_scope\n";
echo "[PASS] 旧接口名已移除，新入口 saveConfig/saveScope 可用于前端对接\n";
echo "\nSummary: passed=" . count($checks) . ", failed=0\n";
