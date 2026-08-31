<?php

/**
 * revenue_pay_channel 删除 pay_channel 后按 pay_type 配置守卫。
 */

$root = dirname(__DIR__);
$client = file_get_contents($root . '/app/AppFactory/Management/Revenue/RevenuePayChannelClient.php');
$model = file_get_contents($root . '/app/AppFactory/Kernel/Model/Revenue/RevenuePayChannelModel.php');
$validator = file_get_contents($root . '/app/management/validate/VRevenuePayChannel.php');
$sql = file_get_contents($root . '/文档说明/新分账数据库初始化.sql');
$changeSql = file_get_contents($root . '/文档说明/revenue_pay_channel新增pay_type.sql');
$failures = [];

foreach ([
    'normalizePayTypeData($postData)',
    "'pay_type' => \$payType",
    "\$data['channel_name'] = \$this->formatPayType(intval(\$data['pay_type']))",
    "\$this->getPayTypeFind(['pay_type' => \$payType, 'status' => 1]",
    '免支付不能配置为分账触发支付类型',
] as $expected) {
    if (strpos($client, $expected) === false) {
        $failures[] = "分账触发配置 Client 缺少 pay_type 逻辑：{$expected}";
    }
}

if (strpos($model, '"pay_type" => "int"') === false) {
    $failures[] = 'RevenuePayChannelModel 未声明 pay_type 字段';
}
if (strpos($model, '"pay_channel" => "int"') !== false) {
    $failures[] = 'RevenuePayChannelModel 仍声明 pay_channel 字段';
}
if (strpos($validator, '"pay_channel"') !== false
    || strpos($validator, '"add" => ["pay_type", "channel_name"]') === false) {
    $failures[] = 'VRevenuePayChannel 未移除 pay_channel 校验或新增场景';
}
if (strpos($sql, '`pay_type` int NOT NULL') === false
    || strpos($sql, '`pay_channel`') !== false
    || strpos($changeSql, 'DROP COLUMN `pay_channel`') === false
    || strpos($changeSql, 'ADD UNIQUE KEY `uk_pay_type` (`pay_type`)') === false
    || strpos($changeSql, 'SET `pay_type` = 0') !== false
    || strpos($changeSql, 'STOP: revenue_pay_channel has unmapped rows') === false) {
    $failures[] = '分账 SQL 未完整切换为 revenue_pay_channel.pay_type';
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] revenue_pay_channel add/update uses pay_type and no longer derives pay_channel\n";
echo "\nSummary: passed=4, failed=0\n";
