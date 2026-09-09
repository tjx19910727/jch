<?php

// 第三方商品同步（嘉潮汇商品同步推送接口）配置。
// 说明：
//  - 配置直接维护在本文件，不再从 .env 读取第三方业务值；
//  - 测试/生产接收服务器由代码中的 env("CglPay.is_test") 选择（与微程支付 WcBase 一致）；
//  - 密钥 apisecret 属服务端机密，严禁暴露到前端/客户端；
//  - 服务器无法读取 config 时可在 runtime/third_party_sync.local.php 提供同名键覆盖。

return [
    // 默认关闭；与接收方联调通过、观察 dirty/api_callback 正常后再显式启用。
    'enabled' => false,

    // 最终生效地址（运行时由 Service 按 env("CglPay.is_test") 从 urls.test / urls.prod 选择；
    // 若在这里或 runtime local 显式填写则优先使用）。
    'core_goods_url' => '',
    'machine_inventory_url' => '',

    // 测试 / 生产接收服务器接口（环境由 env("CglPay.is_test") 判定）：
    //   测试环境 https://test-admin-weicheng.jchtechnologies.com/msvc-shop
    //   生产环境 https://admin-weicheng.jchtechnologies.com/msvc-shop
    'urls' => [
        'test' => [
            // 核心商品 syncGoods：POST {域名}/v1/jiachaohui/syncGoods
            'core_goods_url' => 'https://test-admin-weicheng.jchtechnologies.com/msvc-shop/v1/jiachaohui/syncGoods',
            // 设备商品 syncMachineProduct：POST {域名}/v1/jiachaohui/syncMachineProduct
            // 报文与签名待第三方文档确认后再启用，避免发送不兼容请求。
            'machine_inventory_url' => '',
        ],
        'prod' => [
            'core_goods_url' => 'https://admin-weicheng.jchtechnologies.com/msvc-shop/v1/jiachaohui/syncGoods',
            'machine_inventory_url' => '',
        ],
    ],

    // 签名密钥（apisecret）：syncGoods 按 MD5(apisecret + product_id + apisecret)
    'secret' => 'revrbOIz9fu0xVQbw9iDfyKkGKwiNwRs',

    'batch_size' => 100,
    'connect_timeout' => 3,
    'request_timeout' => 10,
];



