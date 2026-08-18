<?php

$controller = file_get_contents(dirname(__DIR__) . '/app/management/controller/machine/MachineChannelStockReport.php');
$client = file_get_contents(dirname(__DIR__) . '/app/AppFactory/Management/Machine/MachineChannelStockReportClient.php');
$openApiPath = dirname(__DIR__) . '/文档说明/设备库存报表在营状态筛选.apifox.openapi.json';
$openApi = json_decode(file_get_contents($openApiPath), true);

$checks = [
    '控制器从视图条件中移除 is_operating' => substr_count($controller, "unset(\$postData['is_operating'])") === 3,
    '列表和导出均透传设备状态' => substr_count($controller, '$isOperating') === 6,
    '客户端仅允许在营和在库' => strpos($client, 'in_array(intval($isOperating), [1, 2], true)') !== false,
    '客户端通过设备 ID 筛选报表视图' => strpos($client, "getMachineColumn(['is_operating' => intval(\$isOperating)], 'm_id')") !== false,
    '无匹配设备时不会退化为无条件查询' => strpos($client, "[\$machineIdField, 'in', \$mIds ?: [0]]") !== false,
    '按设备导出使用视图设备字段别名' => strpos($client, "applyStockReportOperatingWhere(\$where, \$isOperating, 'mcs.m_id')") !== false,
    '控制器不再自行注入组织条件' => strpos($controller, "\$where['ao_id'] = \$this->manager['ao_id'];") === false,
    '列表和两种导出统一应用数据权限' => substr_count($client, 'applyStockReportDataScope($where') === 4,
    '子账号按授权设备查询' => strpos($client, "intval(\$this->manager['pid'] ?? 0) > 0") !== false
        && strpos($client, "[\$machineIdField, 'in', \$mIds ?: [0]]") !== false,
    '主账号按组织查询' => strpos($client, "\$where[\$aoIdField] = intval(\$this->manager['ao_id'] ?? 0)") !== false,
    '空结果不创建导出任务' => strpos($client, "return \$this->rFail('暂无可导出的库存报表数据');") !== false,
    'OpenAPI 包含列表接口' => isset($openApi['paths']['/management/machine.machine_channel_stock_report/getList']['post']),
    'OpenAPI 状态枚举正确' => ($openApi['components']['schemas']['StockReportFilter']['properties']['is_operating']['enum'] ?? []) === [1, 2],
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

echo "OK: machine channel stock report operating filter guard passed\n";
