<?php

/**
 * 实际导入数据与全场景补全 INSERT 覆盖检查。
 */

$root = dirname(__DIR__);
$supplement = file_get_contents($root . '/文档说明/新分账全场景配置数据.sql');
$importPath = $argv[1] ?? '';
$imported = $importPath && is_file($importPath) ? file_get_contents($importPath) : '';
$failures = [];

$statements = array_values(array_filter(array_map('trim', explode(';', $supplement))));
foreach ($statements as $statement) {
    if (stripos($statement, 'INSERT INTO ') !== 0) {
        $failures[] = '全场景补全文件包含非 INSERT 语句';
        break;
    }
}

$scenarios = [
    '普通全额即时分账',
    '普通比例A40_B60_T1',
    '设备出租组织35全额分账',
    '设备出租固定金额5元',
    '设备出租全额',
    '设备固定比例A20_B30',
    '设备固定金额5元',
    '设备全额分账',
    '设备分账扣除出租基数',
    '设备月营业额阶梯',
    '设备阶梯支付成功金额口径',
    '设备跨阶梯拆分',
    '设备商品比例10',
    '设备商品每件3元_T1',
];
foreach ($scenarios as $scenario) {
    if (strpos($supplement, $scenario) === false) {
        $failures[] = "缺少场景：{$scenario}";
    }
}

foreach ([
    "VALUES(900006, 'JCHM-H2D-0064_设备商品比例10', 4",
    'VALUES(900008, 900006, 123, 1, 2, 63, 1, 10.000',
    'VALUES(900009, 900007, 123, 1, 3, 64, 2, 3.000',
    "VALUES(900012, 'JCHM-H2D-0064_设备分账扣除出租基数', 3, 2, 1",
    "VALUES(900013, 'JCHM-H2D-0064_设备阶梯支付成功金额口径', 3, 1, 2",
    "VALUES(900014, 'JCHM-H2D-0064_设备跨阶梯拆分', 3, 1, 1, 2",
    'VALUES(3, 3, NULL, 1, 4, 62, 4, 0.000',
] as $requiredSql) {
    if (strpos($supplement, $requiredSql) === false) {
        $failures[] = "补全 SQL 缺少关键配置：{$requiredSql}";
    }
}

if (preg_match('/INSERT\s+INTO\s+(?:kiosk\.)?revenue_rule\s*\([^)]*payer_ao_id/is', $supplement)) {
    $failures[] = '全场景补全 SQL 的 revenue_rule INSERT 不应继续包含 payer_ao_id';
}

preg_match_all('/VALUES\\(9\\d{5}, 9\\d{5}, 127, 1, \\d+, ([12]),/u', $supplement, $bindings);
$activeCount = count(array_filter($bindings[1] ?? [], fn($status) => $status === '1'));
if ($activeCount !== 4) {
    $failures[] = "设备127应仅启用4种模式各一个主规则，实际启用{$activeCount}个绑定";
}

if ($imported !== '') {
    foreach ([
        'VALUES(2, 1, 63',
        'VALUES(3, 1, 64',
        'VALUES(4, 1, 62',
        'VALUES(6, 35, 59',
        'VALUES(2, 11, 1',
        'VALUES(3, 12, 1',
        'VALUES(4, 22, 2',
        'VALUES(5, 21, 2',
        'VALUES(6, 4, 4',
    ] as $requiredImport) {
        if (strpos($imported, $requiredImport) === false) {
            $failures[] = "实际导入文件缺少前置数据：{$requiredImport}";
        }
    }
}

if ($failures) {
    foreach ($failures as $failure) echo "[FAIL] {$failure}\n";
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 全场景补全文件仅包含 INSERT 语句\n";
echo "[PASS] 14 类分账配置场景已补全\n";
echo "[PASS] 商品、扣除出租基数、营业额口径和跨阶梯配置正确\n";
echo "[PASS] 设备127仅启用每种模式的主规则，备用场景保持停用\n";
if ($imported !== '') echo "[PASS] 实际导入文件包含补全场景所需账户和支付渠道\n";
echo "\nSummary: passed=" . ($imported !== '' ? 5 : 4) . ", failed=0\n";
