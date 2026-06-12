<?php

return [
    // 四位特殊编码。留空时不启用特殊编码绕过订单校验。
    'special_code' => env('refund_goods.special_code', ''),
];
