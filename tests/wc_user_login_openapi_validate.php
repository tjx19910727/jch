<?php

$path = dirname(__DIR__) . '/文档说明/微程用户登录HTTP兜底.apifox.openapi.json';
$doc = json_decode(file_get_contents($path), true);

$checks = [];
$checks['valid json'] = is_array($doc) && json_last_error() === JSON_ERROR_NONE;
$checks['openapi version'] = ($doc['openapi'] ?? '') === '3.0.3';
$checks['webhook documented'] = isset($doc['paths']['/pay/notify.wei_cheng/scanNotify']['post']);
$checks['device api documented'] = isset($doc['paths']['/machine/receive/getWcLatestLoginInfo']['post']);
$checks['webhook plain ok response'] = isset($doc['paths']['/pay/notify.wei_cheng/scanNotify']['post']['responses']['200']['content']['text/plain']);
$checks['webhook 422 response'] = isset($doc['paths']['/pay/notify.wei_cheng/scanNotify']['post']['responses']['422']);
$deviceResponses = $doc['paths']['/machine/receive/getWcLatestLoginInfo']['post']['responses'] ?? [];
$checks['device business codes stay under http 200'] = isset($deviceResponses['200'])
    && !isset($deviceResponses['100'])
    && !isset($deviceResponses['300']);
$checks['device mac header'] = false;
foreach ($doc['paths']['/machine/receive/getWcLatestLoginInfo']['post']['parameters'] ?? [] as $parameter) {
    if (($parameter['name'] ?? '') === 'mac' && ($parameter['in'] ?? '') === 'header' && !empty($parameter['required'])) {
        $checks['device mac header'] = true;
    }
}
$deviceRequest = $doc['components']['schemas']['GetLatestLoginInfoRequest'] ?? [];
$checks['device required fields'] = ($deviceRequest['required'] ?? []) === ['msg_id', 'machine_id', 'timestamp', 'sign'];
$checks['polling cursor documented'] = isset($deviceRequest['properties']['last_login_info_id']);
$checks['login info id response documented'] = isset($doc['components']['schemas']['LoginInfoData']['properties']['login_info_id']);
$checks['base url variable'] = ($doc['servers'][0]['url'] ?? '') === '{{baseUrl}}';

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) $failed[] = $name;
}

exit($failed ? 1 : 0);
