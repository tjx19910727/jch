<?php

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/management/controller/machine/MachineCheckStock.php');
$openApi = json_decode(
    file_get_contents($root . '/文档说明/库存盘点批次查询接口.apifox.openapi.json'),
    true
);

$detailExample = $openApi['paths']['/management/machine.machine_check_stock/getList']
    ['post']['responses']['200']['content']['application/json']['example']
    ['data']['data'][0] ?? [];

$checks = [
    '盘点明细查询选择m_id' => strpos(
        preg_replace('/\s+/', '', $controller),
        'id,m_id,machine_id,machine_name'
    ) !== false,
    'OpenAPI明细响应包含m_id' => array_key_exists('m_id', $detailExample),
];

$failed = [];
foreach ($checks as $name => $passed) {
    if (!$passed) $failed[] = $name;
}

if ($failed) {
    fwrite(STDERR, "FAILED:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "OK: machine check stock m_id guard passed\n";
