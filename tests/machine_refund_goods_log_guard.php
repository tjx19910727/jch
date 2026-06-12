<?php

$root = dirname(__DIR__);
$api = file_get_contents($root . '/app/AppFactory/Machine/Receive/ApiClient.php');
$controller = file_get_contents($root . '/app/machine/controller/Receive.php');
$validator = file_get_contents($root . '/app/machine/validate/VReceive.php');
$sql = file_get_contents($root . '/数据库更新.sql');
$config = file_get_contents($root . '/config/refund_goods.php');
$failures = [];

$checks = [
    [strpos($controller, 'public function submitRefundGoodsLog()') !== false, '设备端控制器缺少退货日志接口'],
    [strpos($api, 'public function submitRefundGoodsLog()') !== false, '设备端服务缺少退货日志逻辑'],
    [strpos($api, "config('refund_goods.special_code')") !== false, '特殊编码未使用配置项'],
    [strpos($api, "->where('m_id', intval(\$this->machine['m_id']))") !== false, '订单后四位校验未限定当前设备'],
    [strpos($api, "->whereLike('trade_no', '%' . \$inputCode)") !== false, '未使用四位编码匹配订单号末尾'],
    [strpos($validator, '"input_code" => "require|regex:/^\\d{4}$/"') !== false, '未校验输入编码必须为四位数字'],
    [strpos($validator, '"submitRefundGoodsLog" =>') !== false, '缺少退货日志接口验证场景'],
    [strpos($sql, 'CREATE TABLE `machine_refund_goods_log`') !== false, '数据库更新脚本缺少退货日志表'],
    [strpos($config, "env('refund_goods.special_code', '')") !== false, '特殊编码配置默认值必须为空'],
];

foreach ($checks as [$passed, $message]) {
    if (!$passed) $failures[] = $message;
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 客户退货日志表、设备接口、订单校验和特殊编码配置完整\n";
echo "\nSummary: passed=9, failed=0\n";
