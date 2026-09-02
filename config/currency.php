<?php

$switchCurrencyCodes = array_filter(array_map('trim', explode(',', (string)env('currency.switch_currency_codes', 'CNY'))));

return [
    // 币种主数据始终读取 currency_info，本文件仅保存运行能力与安全阈值。
    'default_code' => 'CNY',
    'switch_state_ttl' => 120,
    'manual_sync_limit' => 200,
    'server_switch_currency_codes' => array_values(array_unique(array_map('strtoupper', $switchCurrencyCodes))),
];
