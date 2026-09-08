<?php

// env 未显式配置时不下发 CNY 默认白名单兜底；切换预检会按 currency_info 启用币种全量放行（见 MachineCurrencySwitchService）。
$switchCurrencyCodesEnv = env('currency.switch_currency_codes');
$switchCodesConfigured = is_string($switchCurrencyCodesEnv) && trim($switchCurrencyCodesEnv) !== '';
if ($switchCodesConfigured) {
    $switchCurrencyCodes = array_filter(array_map('trim', explode(',', $switchCurrencyCodesEnv)));
} else {
    $switchCurrencyCodes = ['CNY'];
}

return [
    // 币种主数据始终读取 currency_info，本文件仅保存运行能力与安全阈值。
    'default_code' => 'CNY',
    'switch_state_ttl' => 120,
    // 是否在 .env 显式配置了可切换币种白名单（供服务端预检判断是否做全量兜底）。
    'switch_codes_configured' => $switchCodesConfigured,
    'server_switch_currency_codes' => array_values(array_unique(array_map('strtoupper', $switchCurrencyCodes))),
];
