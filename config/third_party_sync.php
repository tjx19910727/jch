<?php

return [
    // 默认关闭；SQL、代码和接收方配置完成后再显式启用。
    'enabled' => (bool)env('third_party_sync.enabled', false),
    // 两类业务分别配置接收地址，发送内容均为 JSON。
    'machine_inventory_url' => env('third_party_sync.machine_inventory_url', ''),
    'core_goods_url' => env('third_party_sync.core_goods_url', ''),
    // app_id 参与报文，secret 只用于服务端 HMAC-SHA256 签名。
    'app_id' => env('third_party_sync.app_id', ''),
    'secret' => env('third_party_sync.secret', ''),
    'batch_size' => intval(env('third_party_sync.batch_size', 100)),
    'connect_timeout' => intval(env('third_party_sync.connect_timeout', 3)),
    'request_timeout' => intval(env('third_party_sync.request_timeout', 10)),
];
