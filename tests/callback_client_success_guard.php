<?php

$root = dirname(__DIR__);
$code = file_get_contents($root . '/app/AppFactory/Api/Send/CallbackClient.php');
$failures = [];

function checkCallbackGuard($condition, $message, &$failures)
{
    if (!$condition) $failures[] = $message;
}

checkCallbackGuard(strpos($code, '$this->isCallbackSuccess($curl)') !== false, '回调主流程未使用业务成功判断', $failures);
checkCallbackGuard(strpos($code, 'protected function isCallbackSuccess($result)') !== false, '缺少回调业务成功判断方法', $failures);
checkCallbackGuard(strpos($code, "if (\$result === 'success') return true;") !== false, '纯文本 success 应保持兼容', $failures);
checkCallbackGuard(strpos($code, "\$this->isCallbackSuccess(\$result['data'])") !== false, 'JSON data=success 应保持兼容', $failures);
checkCallbackGuard(strpos($code, "isset(\$result['success']) && \$result['success'] === true") !== false, 'JSON success=true 应保持兼容', $failures);
checkCallbackGuard(strpos($code, "intval(\$result[\$field]) === 200") === false, '不得仅凭 status/code/http_code=200 判定业务成功', $failures);
checkCallbackGuard(strpos($code, "isset(\$result['ok']) && \$result['ok'] === true") === false, '不得仅凭 ok=true 判定业务成功', $failures);

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 回调成功判断已收窄\n";

