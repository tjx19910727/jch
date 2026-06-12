<?php

$root = dirname(__DIR__);
$files = [
    'notify' => file_get_contents($root . '/app/pay/controller/notify/WeiCheng.php'),
    'api' => file_get_contents($root . '/app/AppFactory/Machine/Receive/ApiClient.php'),
    'controller' => file_get_contents($root . '/app/machine/controller/Receive.php'),
    'validator' => file_get_contents($root . '/app/machine/validate/VReceive.php'),
    'sql' => file_get_contents($root . '/数据库更新.sql'),
];
$loginCreatePos = strpos($files['notify'], 'WcUserLoginInfoModel::create');
$mqSendPos = strpos($files['notify'], "sendMq('scanNotify'");
$mqTryPos = strrpos(substr($files['notify'], 0, $mqSendPos), 'try {');

$checks = [
    'machine code compatibility' => strpos($files['notify'], "\$postData['machine code']") !== false,
    'login info persisted before mq send' => $loginCreatePos !== false && $loginCreatePos < $mqSendPos,
    'login persistence failure can trigger provider retry' => $loginCreatePos !== false
        && $mqTryPos !== false
        && $loginCreatePos < $mqTryPos,
    'mq status success recorded' => strpos($files['notify'], 'updateLoginInfoMqStatus($loginInfo->wuli_id, 1') !== false,
    'mq status failure recorded' => strpos($files['notify'], 'updateLoginInfoMqStatus($loginInfo->wuli_id, 2') !== false,
    'mq failures do not return business failure' => strpos($files['notify'], 'mq_send_fail') === false
        && strpos($files['notify'], 'mq_send_exception') === false,
    'webhook returns explicit plain text acknowledgement' => strpos($files['notify'], "return \$this->textResponse('ok')") !== false
        && strpos($files['notify'], "'Content-Type' => 'text/plain; charset=utf-8'") !== false,
    'invalid webhook data is rejected' => strpos($files['notify'], "textResponse('phone_required', 422)") !== false
        && strpos($files['notify'], "textResponse('machine_id_required', 422)") !== false
        && strpos($files['notify'], "textResponse('machine_not_found', 422)") !== false,
    'device api exists' => strpos($files['api'], 'function getWcLatestLoginInfo()') !== false,
    'device api filters current machine' => strpos($files['api'], "\$this->machine['machine_id']") !== false,
    'device api limits to 120 seconds' => strpos($files['api'], 'time() - 120') !== false,
    'device api supports polling cursor' => strpos($files['api'], "\$this->data['last_login_info_id']") !== false
        && strpos($files['api'], "['wuli_id', '>', \$lastLoginInfoId]") !== false,
    'device api returns login info id' => strpos($files['api'], "\$data['login_info_id']") !== false,
    'controller action exists' => strpos($files['controller'], 'function getWcLatestLoginInfo()') !== false,
    'validator scene exists' => strpos($files['validator'], '"getWcLatestLoginInfo" => ["msg_id","machine_id","timestamp","sign","last_login_info_id"]') !== false,
    'polling cursor validates integer' => strpos($files['validator'], '"last_login_info_id" => "integer"') !== false,
    'table migration exists' => strpos($files['sql'], 'CREATE TABLE `wc_user_login_info`') !== false,
    'machine time index exists' => strpos($files['sql'], 'KEY `idx_machine_time` (`machine_id`,`create_time`)') !== false,
];

$failed = [];
foreach ($checks as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
    if (!$passed) {
        $failed[] = $name;
    }
}

exit($failed ? 1 : 0);
