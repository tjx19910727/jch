<?php

$root = dirname(__DIR__);
$paymentCode = file_get_contents($root . '/app/AppFactory/Kernel/Traits/Payment/AfterOrderPaymentTrait.php');
$wcCode = file_get_contents($root . '/app/AppFactory/Kernel/Traits/WeiCheng/WcBaseTrait.php');
$failures = [];

function checkPaymentSyncGuard($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

checkPaymentSyncGuard(strpos($paymentCode, 'protected function syncOrderToWcAfterPayment()') !== false, '缺少支付成功后的微程同步隔离方法', $failures);
checkPaymentSyncGuard(strpos($paymentCode, '$this->syncOrderToWcAfterPayment();') !== false, 'paymentSuccessful 未调用隔离后的微程同步方法', $failures);
checkPaymentSyncGuard(strpos($paymentCode, '$flag[] = $this->orderSync2Wc($this->order)') === false, '微程同步结果不应进入支付成功事务 flag', $failures);
checkPaymentSyncGuard(strpos($paymentCode, 'catch (\Throwable $e)') !== false, '微程同步异常需要被捕获', $failures);
checkPaymentSyncGuard(strpos($paymentCode, '微程订单同步异常，不影响支付成功事务') !== false, '缺少微程同步异常日志', $failures);
checkPaymentSyncGuard(
    strpos($wcCode, 'isset($value[\'real_channel_code\'])') !== false
        && strpos($wcCode, '$realChannelCode == \'Z10\'') !== false
        && strpos($wcCode, '$wc_order_no[\'real_channel_code\']') === false,
    '微程同步 dispensing_status 应安全读取当前子商品 real_channel_code',
    $failures
);
checkPaymentSyncGuard(
    strpos($wcCode, '微程返回格式异常') !== false
        && strpos($wcCode, '($res_arr[\'status\'] ?? \'\')') !== false
        && strpos($wcCode, '$res_arr[\'order_no\'] ?? \'\'') !== false,
    '微程同步需要容错异常响应',
    $failures
);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 支付成功状态不再被微程同步失败回滚\n";
echo "[PASS] 微程同步 payload 和返回解析已容错\n";

