<?php

$controller = file_get_contents(dirname(__DIR__) . '/app/management/controller/sale/SaleOrders.php');
$openApiPath = dirname(__DIR__) . '/文档说明/订单列表小票信息.apifox.openapi.json';
$openApi = json_decode(file_get_contents($openApiPath), true);

$checks = [
    '订单列表查询包含小票字段' => strpos($controller, 'pay_code, mobile,receipt,{$costPriceField}') !== false,
    '订单列表接口文档存在' => isset($openApi['paths']['/management/sale.sale_orders/getList']['post']),
    '响应订单声明 receipt 字段' => isset($openApi['components']['schemas']['SaleOrderListItem']['properties']['receipt']),
    'receipt 字段允许历史空值' => ($openApi['components']['schemas']['SaleOrderListItem']['properties']['receipt']['nullable'] ?? false) === true,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) {
        $failed[] = $name;
    }
}

exit($failed ? 1 : 0);
