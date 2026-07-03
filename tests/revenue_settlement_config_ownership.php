<?php

/**
 * 结算时间配置归属检查。
 *
 * 渠道表只负责控制支付渠道是否触发分账；
 * 每条分账订单从实际命中的 revenue_rule_config 写入结算时间快照。
 */

$root = dirname(__DIR__);
$calculator = file_get_contents($root . '/app/AppFactory/Kernel/Service/Revenue/RevenueCalculator.php');
$channelClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenuePayChannelClient.php');
$channelModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenuePayChannelModel.php');
$channelValidator = file_get_contents($root . '/app/management/validate/VRevenuePayChannel.php');
$channelController = file_get_contents($root . '/app/management/controller/revenue/RevenuePayChannel.php');
$ruleClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$ruleModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenueRuleConfigModel.php');
$databaseChange = file_get_contents($root . '/文档说明/统一分账配置数据库变更.sql');
$failures = [];

if (strpos($calculator, "\$rule['settlement_type']") === false
    || strpos($calculator, "\$rule['settlement_days']") === false) {
    $failures[] = '分账订单没有从实际命中规则读取结算配置';
}
if (strpos($calculator, 'payeeRevenueConfig') !== false
    || strpos($calculator, 'revenue_payee_config') !== false) {
    $failures[] = '分账计算器仍依赖收款策略新分账配置';
}
if (strpos($channelModel, 'settlement_type') !== false) {
    $failures[] = '渠道模型仍包含结算时间配置';
}
if (strpos($channelModel, 'payee_type') !== false) {
    $failures[] = '渠道模型仍包含已删除的 payee_type';
}
if (strpos($channelValidator, 'payee_type') !== false) {
    $failures[] = '渠道验证器仍包含已删除的 payee_type';
}
if (substr_count($channelController, "unset(\$postData['payee_type']);") < 2) {
    $failures[] = '渠道查询接口没有忽略旧前端误传的 payee_type';
}
if (strpos($channelClient, "unset(\$postData['payee_type'], \$postData['settlement_type'], \$postData['settlement_days'])") === false) {
    $failures[] = '渠道接口没有忽略误传的旧字段或结算时间字段';
}
if (strpos($ruleClient, 'T+N 分账天数必须大于0') === false
    || strpos($ruleModel, 'settlement_type') === false) {
    $failures[] = '分账规则未接管结算配置及校验';
}

preg_match('/CREATE TABLE IF NOT EXISTS `revenue_pay_channel` \\((.*?)\\) ENGINE=/s', $databaseChange, $channelTable);
preg_match('/CREATE TABLE IF NOT EXISTS `revenue_rule_config` \\((.*?)\\) ENGINE=/s', $databaseChange, $ruleTable);
if (strpos($channelTable[1] ?? '', '`settlement_type`') !== false) {
    $failures[] = '初始化 SQL 的 revenue_pay_channel 仍包含结算时间字段';
}
if (strpos($channelTable[1] ?? '', '`payee_type`') !== false) {
    $failures[] = '初始化 SQL 的 revenue_pay_channel 仍包含已删除的 payee_type';
}
if (strpos($ruleTable[1] ?? '', '`settlement_type`') === false
    || strpos($ruleTable[1] ?? '', '`settlement_days`') === false) {
    $failures[] = '初始化 SQL 的 revenue_rule_config 缺少结算时间字段';
}
if (strpos($databaseChange, 'CREATE TABLE IF NOT EXISTS `revenue_rule`') !== false
    || strpos($databaseChange, 'CREATE TABLE IF NOT EXISTS `revenue_payee_config`') !== false) {
    $failures[] = '统一配置初始化 SQL 仍创建旧分账表';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 渠道表只负责控制是否触发新分账\n";
echo "[PASS] 分账规则负责结算时间配置\n";
echo "[PASS] 分账订单从实际命中规则写入结算快照\n";
echo "\nSummary: passed=3, failed=0\n";
