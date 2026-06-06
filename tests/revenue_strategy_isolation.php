<?php

/**
 * 新分账与旧收款策略隔离检查。
 *
 * 允许新分账只读 strategy_payee/sp_id；
 * 禁止旧收款策略后台调用新分账服务；
 * 禁止新分账模块写入或修改 strategy_payee。
 */

$root = dirname(__DIR__);
$failures = [];

$legacyPaths = [
    'app/management/controller/strategy',
    'app/AppFactory/Management/Strategy',
    'app/AppFactory/Kernel/Model/Strategy',
    'app/AppFactory/Kernel/Traits/Strategy',
    'app/management/validate/VStrategyPayee.php',
];

$revenuePaths = [
    'app/management/controller/revenue',
    'app/AppFactory/Management/Revenue',
    'app/AppFactory/Kernel/Model/Revenue',
    'app/AppFactory/Kernel/Traits/Revenue',
    'app/AppFactory/Kernel/Service/Revenue',
    '文档说明/分账逻辑优化数据库变更.sql',
];

$legacyForbidden = [
    '/revenuePayeeConfig/i',
    '/RevenuePayeeConfig/i',
    '/revenue_account/i',
    '/revenue_rule/i',
    '/revenue_order/i',
    '/default_ra_id/i',
    '/enable_revenue/i',
];

$revenueForbidden = [
    '/ALTER\s+TABLE\s+`?strategy_payee/i',
    '/UPDATE\s+`?strategy_payee/i',
    '/INSERT\s+INTO\s+`?strategy_payee/i',
    '/DELETE\s+FROM\s+`?strategy_payee/i',
    '/addStrategyPayee\s*\(/i',
    '/updateStrategyPayee\s*\(/i',
    '/delStrategyPayee\s*\(/i',
];

scanPaths($root, $legacyPaths, $legacyForbidden, '旧收款策略模块引用新分账', $failures);
scanPaths($root, $revenuePaths, $revenueForbidden, '新分账模块写入旧收款策略', $failures);

if ($failures) {
    foreach ($failures as $failure) {
        echo "[FAIL] {$failure}\n";
    }
    echo "\nSummary: failed=" . count($failures) . "\n";
    exit(1);
}

echo "[PASS] 旧收款策略模块未引用新分账服务或字段\n";
echo "[PASS] 新分账模块未写入或修改 strategy_payee\n";
echo "[PASS] 新分账可继续只读 strategy_payee/sp_id 作为收款结果输入\n";
echo "\nSummary: passed=3, failed=0\n";

function scanPaths(string $root, array $paths, array $patterns, string $label, array &$failures): void
{
    foreach ($paths as $relativePath) {
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!file_exists($path)) {
            continue;
        }
        foreach (filesOf($path) as $file) {
            $content = file_get_contents($file);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $relativeFile = str_replace('\\', '/', substr($file, strlen($root) + 1));
                    $failures[] = "{$label}: {$relativeFile} 命中 {$pattern}";
                }
            }
        }
    }
}

function filesOf(string $path): array
{
    if (is_file($path)) {
        return [$path];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php', 'sql'], true)) {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}
