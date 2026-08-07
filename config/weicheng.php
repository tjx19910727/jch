<?php

return [
    // 仅在 CglPay.is_test=true 时允许使用，生产环境始终不生效。
    'virtual_login' => [
        'enabled' => env('weicheng.virtual_login_enabled', true),
        'phone' => env('weicheng.virtual_login_phone', '13000000000'),
        'code' => env('weicheng.virtual_login_code', '000000'),
    ],
];
