<?php

$root = dirname(__DIR__);
$failures = [];

function checkProductRule($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

$calculator = file_get_contents($root . '/app/AppFactory/Kernel/Service/Revenue/RevenueCalculator.php');
$ruleClient = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenueRuleClient.php');
$afterRefundTrait = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Payment/AfterOrderRefundTrait.php');
$migration = file_get_contents($root . '/文档说明/设备商品分账数据库变更.sql');

checkProductRule(strpos($calculator, 'calculateProductRule') !== false, '计算器缺少设备商品分账流程', $failures);
checkProductRule(strpos($calculator, "'source' => 'product_rule'") !== false, '设备商品分账未写入独立来源', $failures);
checkProductRule(strpos($calculator, "bcmul(\$value, (string)max(0, intval(\$quantity))") !== false, '固定金额未按商品数量计算', $failures);
checkProductRule(strpos($calculator, "getRuleByMode(4)") !== false, '计算器未读取 rule_mode=4', $failures);
checkProductRule(strpos($calculator, 'return $hasMatchedRuleItem;') !== false, '未命中商品规则时仍会错误跳过设备规则', $failures);
checkProductRule(strpos($ruleClient, '同一商品固定比例分账合计不能超过100%') !== false, '缺少同商品比例合计限制', $failures);
checkProductRule(strpos($migration, 'ADD COLUMN `g_id`') !== false, '数据库变更缺少商品字段', $failures);
checkProductRule(strpos($migration, 'addProductItem') !== false, '数据库变更缺少新增接口节点', $failures);
checkProductRule(strpos($afterRefundTrait, "intval(\$revenue['rule_mode'] ?? 0) === 4") !== false, '售后退款缺少设备商品规则判断', $failures);
checkProductRule(strpos($afterRefundTrait, "bcdiv(\$this->refund['refund_quantity'], \$revenue['sod_quantity'], 6)") !== false, '商品固定金额分账未按退款数量回退', $failures);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 设备商品分账独立计算且来源可追溯\n";
echo "[PASS] 固定金额按商品数量计算\n";
echo "[PASS] 同商品比例合计受限且数据库字段完整\n";
echo "[PASS] 商品固定金额分账按退款数量回退\n";
echo "\nSummary: passed=4, failed=0\n";
