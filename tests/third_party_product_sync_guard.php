<?php

$root = dirname(__DIR__);
$v2 = file_get_contents($root . '/app/AppFactory/Api/V2/V2Client.php');
$snapshot = file_get_contents($root . '/app/AppFactory/Kernel/Service/Api/ThirdPartyProductSnapshotService.php');
$sync = file_get_contents($root . '/app/AppFactory/Kernel/Service/Api/ThirdPartyProductSyncService.php');
$callback = file_get_contents($root . '/app/AppFactory/Api/Send/CallbackClient.php');
$console = file_get_contents($root . '/config/console.php');
$config = file_get_contents($root . '/config/third_party_sync.php');
$sql = file_get_contents($root . '/文档说明/V2第三方商品主动同步数据库变更.sql');
$openApi = json_decode(file_get_contents($root . '/文档说明/V2第三方商品主动同步.apifox.openapi.json'), true);

$checks = [
    'V2 两个查询复用统一快照服务' => substr_count($v2, 'new ThirdPartyProductSnapshotService()') >= 2,
    '核心商品固定 ao_id=17' => strpos($snapshot, 'public const CORE_AO_ID = 17') !== false,
    '设备字段沿用 get_inventory_list 口径' => strpos($snapshot, 'mismatch_quantity') !== false
        && strpos($snapshot, 'reserver_quantity') !== false
        && strpos($snapshot, 'slot_max_count') !== false,
    '设备商品采用完整快照' => strpos($sync, "TYPE_MACHINE_INVENTORY = 'machine_inventory'") !== false,
    '核心商品支持删除墓碑' => strpos($sync, "? 'delete' : 'upsert'") !== false,
    '同步任务本地事务加行锁' => strpos($sync, 'Db::transaction') !== false && strpos($sync, '->lock(true)') !== false,
    '聚合记录使用持续递增版本水位' => strpos($sync, "->whereColumn('version', '>', 'dispatched_version')") !== false
        && strpos($sync, "'dispatched_version' => \$version") !== false,
    '推送复用 api_callback' => strpos($sync, 'ApiCallbackModel::create') !== false,
    '回调类型 12 和 13 已配置重试' => strpos($callback, '"12" =>') !== false && strpos($callback, '"13" =>') !== false,
    '新回调使用独立超时配置' => strpos($callback, "config('third_party_sync.connect_timeout')") !== false
        && strpos($callback, "config('third_party_sync.request_timeout')") !== false,
    '配置默认关闭' => strpos($config, "env('third_party_sync.enabled', false)") !== false,
    '控制台命令已注册' => strpos($console, "'third_party_sync' => 'app\\command\\ThirdPartySync'") !== false,
    '数据库聚合键唯一' => strpos($sql, 'UNIQUE KEY `uk_sync_aggregate` (`sync_type`,`aggregate_id`)') !== false,
    '数据库保存已派发版本水位' => strpos($sql, '`dispatched_version` bigint unsigned NOT NULL DEFAULT 0') !== false,
    'machine_channel 三类 Trigger 完整' => substr_count($sql, 'CREATE TRIGGER `trg_third_party_machine_channel_') === 3,
    'goods 三类 Trigger 完整' => substr_count($sql, 'CREATE TRIGGER `trg_third_party_goods_') === 3,
    '数据库 Trigger 不直接发送 HTTP' => stripos($sql, 'curl') === false && stripos($sql, 'http://') === false && stripos($sql, 'https://') === false,
    'OpenAPI 包含两个第三方接收接口' => isset($openApi['paths']['/v2/machine-inventory/update']['post'])
        && isset($openApi['paths']['/v2/core-goods/update']['post']),
    'OpenAPI 包含新增修改和删除示例' => isset($openApi['paths']['/v2/core-goods/update']['post']['requestBody']['content']['application/json']['examples']['新增或修改商品'])
        && isset($openApi['paths']['/v2/core-goods/update']['post']['requestBody']['content']['application/json']['examples']['删除商品']),
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

echo "OK: third-party product sync guard passed (" . count($checks) . " checks)\n";
