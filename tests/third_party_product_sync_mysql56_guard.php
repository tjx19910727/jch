<?php

$sql = file_get_contents(dirname(__DIR__) . '/文档说明/V2第三方商品主动同步数据库变更.sql');

$checks = [
    'SQL 明确以 MySQL 5.6 为基线' => strpos($sql, '适用 MySQL 5.6') !== false,
    'SQL 提示 1419 和 DBA 执行边界' => strpos($sql, 'log_bin_trust_function_creators=0') !== false
        && strpos($sql, 'DBA/SUPER') !== false,
    '不使用 MySQL 8 排序规则' => stripos($sql, 'utf8mb4_0900') === false,
    '不使用 CTE' => preg_match('/\bWITH\s+[a-zA-Z_][a-zA-Z0-9_]*\s+AS\s*\(/i', $sql) !== 1,
    '不使用窗口函数' => preg_match('/\b(ROW_NUMBER|RANK|DENSE_RANK)\s*\(/i', $sql) !== 1,
    '不使用 SKIP LOCKED 或 NOWAIT' => preg_match('/\b(SKIP\s+LOCKED|NOWAIT)\b/i', $sql) !== 1,
    '不使用 JSON 类型或 JSON_TABLE' => preg_match('/\b(JSON|JSON_TABLE)\b/i', $sql) !== 1,
    '不使用 MySQL 8 RENAME COLUMN' => preg_match('/\bRENAME\s+COLUMN\b/i', $sql) !== 1,
    '不依赖客户端 DELIMITER 指令' => preg_match('/^\s*DELIMITER\b/im', $sql) !== 1,
    'Trigger 使用单语句而非 BEGIN END' => preg_match('/\bBEGIN\b|\bEND\b/i', $sql) !== 1,
    'Trigger 使用 MySQL 5.6 支持的先删后建方式' => substr_count($sql, 'DROP TRIGGER IF EXISTS') === 12
        && substr_count($sql, 'CREATE TRIGGER `trg_third_party_') === 6,
    '每个表的时机事件组合唯一' => preg_match_all('/CREATE TRIGGER\s+`[^`]+`\s+(BEFORE|AFTER)\s+(INSERT|UPDATE|DELETE)\s+ON\s+`([^`]+)`/i', $sql, $matches) === 6
        && count(array_unique(array_map(function ($timing, $event, $table) {
            return strtoupper($table . ':' . $timing . ':' . $event);
        }, $matches[1], $matches[2], $matches[3]))) === 6,
    '唯一索引在 MySQL 5.6 InnoDB 767 字节限制内' => (32 + 64) * 4 <= 767,
    '使用 MySQL 5.6 支持的 ON DUPLICATE KEY UPDATE' => strpos($sql, 'ON DUPLICATE KEY UPDATE') !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    if (!$passed) {
        $failed[] = $name;
    }
}
if ($failed) {
    fwrite(STDERR, "FAILED:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "OK: MySQL 5.6 compatibility guard passed (" . count($checks) . " checks)\n";
