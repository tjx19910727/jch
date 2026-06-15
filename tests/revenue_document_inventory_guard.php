<?php

/**
 * 新分账文档保留清单守卫。
 */

$root = dirname(__DIR__);
$documentDir = $root . '/文档说明';
$allowed = [
    '新分账最新说明.md',
    '新分账后台接口.apifox.openapi.json',
    '新分账数据库初始化.sql',
    '新分账最新数据库升级.sql',
    '设备商品分账数据库变更.sql',
    '删除revenue_rule.payer_ao_id.sql',
    '新分账配置自检.sql',
    '新分账全场景配置数据.sql',
];
$actual = [];

foreach (scandir($documentDir) as $file) {
    if (preg_match('/分账|revenue/i', $file)) {
        $actual[] = $file;
    }
}
sort($allowed);
sort($actual);

if ($actual !== $allowed) {
    echo "[FAIL] 新分账文档清单不符合约定\n";
    echo "expected=" . json_encode($allowed, JSON_UNESCAPED_UNICODE) . "\n";
    echo "actual=" . json_encode($actual, JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

echo "[PASS] 新分账目录仅保留最新说明、数据操作SQL和Apifox JSON\n";
echo "[PASS] 历史规划、过渡方案和重复文档已清理\n";
echo "\nSummary: passed=2, failed=0\n";
