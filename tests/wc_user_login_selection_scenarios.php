<?php

function selectLatestLogin(array $rows, $machineId, $now, $lastLoginInfoId = 0)
{
    $rows = array_filter($rows, function ($row) use ($machineId, $now, $lastLoginInfoId) {
        return $row['machine_id'] === $machineId
            && $row['create_time'] >= $now - 120
            && $row['wuli_id'] > $lastLoginInfoId;
    });
    usort($rows, function ($left, $right) {
        if ($left['create_time'] === $right['create_time']) {
            return $right['wuli_id'] <=> $left['wuli_id'];
        }
        return $right['create_time'] <=> $left['create_time'];
    });
    return $rows ? reset($rows) : null;
}

$now = 2000;
$rows = [
    ['wuli_id' => 10, 'machine_id' => 'A', 'create_time' => 1879],
    ['wuli_id' => 11, 'machine_id' => 'A', 'create_time' => 1880],
    ['wuli_id' => 12, 'machine_id' => 'B', 'create_time' => 1999],
    ['wuli_id' => 13, 'machine_id' => 'A', 'create_time' => 1990],
    ['wuli_id' => 14, 'machine_id' => 'A', 'create_time' => 1990],
];

$checks = [
    'older than 120 seconds excluded' => selectLatestLogin([$rows[0]], 'A', $now) === null,
    'exactly 120 seconds included' => selectLatestLogin([$rows[1]], 'A', $now)['wuli_id'] === 11,
    'other machine excluded' => selectLatestLogin([$rows[2]], 'A', $now) === null,
    'latest record selected' => selectLatestLogin($rows, 'A', $now)['wuli_id'] === 14,
    'cursor prevents duplicate delivery' => selectLatestLogin($rows, 'A', $now, 14) === null,
    'cursor still returns newer record' => selectLatestLogin($rows, 'A', $now, 13)['wuli_id'] === 14,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
