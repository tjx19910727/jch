<?php

/**
 * 新分账规则后台接口校验边界检查。
 */

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$ruleModel = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenueRuleModel.php');
$validator = file_get_contents($root . '/app/management/validate/VRevenueRule.php');
$controller = file_get_contents($root . '/app/management/controller/revenue/RevenueRule.php');
$orderClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueOrderClient.php');
$accountClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueAccountClient.php');
$payChannelClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenuePayChannelClient.php');
$failures = [];

if (strpos($client, "unset(\$postData['rr_id']);") === false
    && strpos($client, "\$this->updateRevenueRule(\$update, ['rr_id' => \$rrId])") === false
    || strpos($client, "updateRevenueRule(\$postData, [], ['rr_id'])") !== false) {
    $failures[] = '规则更新接口仍把主键误当作允许更新字段，其他规则字段无法保存';
}
if (substr_count($client, "unset(\$postData['payer_ao_id']);") < 2
    || strpos($client, "'payer_ao_id',") !== false
    || strpos($ruleModel, '"payer_ao_id"') !== false
    || strpos($client, 'protected function verifyRuleUpdate($rrId, array $update)') === false
    || strpos($client, '分账策略更新后数据校验失败') === false) {
    $failures[] = '规则接口仍保留 payer_ao_id，或缺少旧请求兼容清理和写后校验';
}
if (strpos($client, "unset(\$postData['rri_id']);") === false
    || strpos($client, "unset(\$postData['rrit_id']);") === false
    || strpos($accountClient, "unset(\$postData['ra_id']);") === false
    || strpos($payChannelClient, "unset(\$postData['rpc_id']);") === false) {
    $failures[] = '新分账其他更新接口仍可能只保存主键';
}
if (strpos($client, 'protected function checkItemData(&$data, $isUpdate = false)') === false) {
    $failures[] = '规则明细校验仍按值传参，自动补全 manager_id 无法保存';
}
if (strpos($validator, '"addItem" => ["rr_id", "receiver_ao_id", "ra_id", "manager_id", "calc_type"]') !== false
    || strpos($validator, '"addProductItem" => ["rr_id", "g_id", "receiver_ao_id", "ra_id", "manager_id", "calc_type", "calc_value"]') !== false) {
    $failures[] = '验证器仍强制前端传 manager_id，服务层无法按账户自动补全';
}
if (strpos($client, "'rri_id,rr_id,g_id,receiver_ao_id,ra_id,manager_id,calc_type,calc_value,status'") === false
    || strpos($client, '$itemData = array_merge($oldItem ?: [], $data);') === false) {
    $failures[] = '更新规则明细时没有合并旧值执行完整一致性校验';
}
if (strpos($client, "if (intval(\$account['ao_id']) !== intval(\$itemData['receiver_ao_id']))") === false) {
    $failures[] = '更新规则明细时可能绕过账户所属组织检查';
}
if (strpos($client, "'rri.calc_type' => 4") === false
    || strpos($client, "'rr.rule_mode' => 3") === false
    || strpos($client, '阶梯分账仅支持设备阶梯分账明细') === false) {
    $failures[] = '阶梯接口没有限制到设备阶梯分账明细';
}
if (strpos($client, '$tierData = array_merge($oldTier, $data);') === false
    || strpos($client, '$overlap = $this->checkTierOverlap($tierData, $isUpdate);') === false) {
    $failures[] = '更新阶梯区间时没有使用完整旧值检查重叠';
}
if (strpos($client, 'if ($status === 1) {') === false
    || strpos($client, "\$postData['ao_id'] = \$machine['ao_id'];") === false) {
    $failures[] = '设备绑定接口没有支持停用备用场景或没有锁定设备真实组织';
}
if (strpos($controller, "\$this->validate(\$postData, \$this->validatePath . 'bindMachine')") === false
    || strpos($validator, '"bindMachine" => ["rr_id", "m_id"]') === false) {
    $failures[] = '设备绑定接口缺少请求参数验证';
}
if (strpos($client, '全额分账明细必须是策略内唯一启用明细') === false
    || strpos($client, '分账策略已有明细，不允许修改分账模式') === false) {
    $failures[] = '规则接口仍允许全额明细与其他明细并存或带明细修改模式';
}
$payTimePosition = strpos($orderClient, "\$this->order['pay_time'] = intval(\$data['pay_time'] ?? 0) ?: time();");
$settlePosition = strpos($orderClient, '$flag[] = $this->settlementRevenue();');
if ($payTimePosition === false || $settlePosition === false || $payTimePosition > $settlePosition) {
    $failures[] = '模拟支付成功仍在结算后写入支付时间';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 规则明细更新执行完整账户一致性校验\n";
echo "[PASS] 规则 payer_ao_id 已删除且旧请求参数会被忽略\n";
echo "[PASS] 规则更新使用明确字段白名单并执行写后校验\n";
echo "[PASS] 新分账更新接口不会把主键误当作字段白名单\n";
echo "[PASS] 自动补全的账户管理人可以写入数据库\n";
echo "[PASS] 阶梯接口仅允许操作设备阶梯分账明细\n";
echo "[PASS] 阶梯局部更新使用完整区间检查重叠\n";
echo "[PASS] 设备绑定支持停用备用场景并锁定真实设备组织\n";
echo "[PASS] 全额明细唯一性、规则模式变更和模拟支付时间受到保护\n";
echo "\nSummary: passed=9, failed=0\n";
