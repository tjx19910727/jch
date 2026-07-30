<?php

$root = dirname(__DIR__);
$api = file_get_contents($root . '/app/AppFactory/Machine/Receive/ApiClient.php');
$config = file_get_contents($root . '/config/weicheng.php');

$checks = [
    'virtual credential defaults are configured' =>
        strpos($config, "'13000000000'") !== false && strpos($config, "'000000'") !== false,
    'virtual login is restricted to test environment' =>
        strpos($api, "env('CglPay.is_test', false)") !== false,
    'sms request bypasses third party for virtual phone' =>
        strpos($api, 'if ($this->isWcVirtualLoginRequest(false))') !== false,
    'login bypass happens before third party login call' =>
        strpos($api, 'if ($this->isWcVirtualLoginRequest(true))')
        < strpos($api, '$res = $this->wcLoginUser('),
    'virtual response reads only local cards and addresses' =>
        strpos($api, "getCardList(['bind_id' => \$phone])") !== false
        && strpos($api, "getWcUserAddressesList(['bind_id' => \$phone])") !== false,
    'virtual response keeps login contract' =>
        strpos($api, "'success' => true") !== false
        && strpos($api, "'card_lists' => array_values(\$cardLists)") !== false
        && strpos($api, "'address_lists' => array_values(\$addressLists)") !== false,
];

foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . "\n";
    if (!$passed) exit(1);
}

echo "wc virtual login guard passed\n";
