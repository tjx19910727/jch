<?php

/**
 * 故障通知固定配置。
 *
 * 微信模板由微信公众号后台添加后固化到代码。后台故障分类只保存template_type，
 * 不保存wt_id，也不允许自由维护模板正文，避免模板字段与发送参数不一致。
 */
return [
    'wechat_templates' => [
        'mFault' => [
            'template_type' => 'mFault',
            'template_name' => '设备异常提醒',
            'display_name' => '设备异常提醒（通用模板）',
            'template_no' => '43033',
            // 正式公众号模板ID。
            'template_id' => 'frqumju8oA7N8msUrhliHpDd18j2le-DxLGlz5jWz8g',
            // CglPay.is_test=true时使用；当前先与正式环境保持一致。
            'test_template_id' => 'frqumju8oA7N8msUrhliHpDd18j2le-DxLGlz5jWz8g',
            'body' => [
                ['设备编码' => ['value' => '{{machine_id}}', 'field' => 'character_string16']],
                ['设备名称' => ['value' => '{{machine_name}}', 'field' => 'thing6']],
                ['异常现象' => ['value' => '{{error_info}}', 'field' => 'thing12']],
                ['异常时间' => ['value' => '{{error_time}}', 'field' => 'time15']],
                // 微信模板字段名称固定为“设备地址”，业务上填故障短名称，不填真实地址。
                ['设备地址' => ['value' => '{{error_code}}', 'field' => 'thing9']],
            ],
        ],
        'mOffline' => [
            'template_type' => 'mOffline',
            'template_name' => '售货机离线通知',
            'display_name' => '售货机离线通知',
            'template_no' => '52646',
            'template_id' => 'AmyBb6IW_AdP5OxcqBniqRdV5o7aUa36XJgphWOCZLs',
            'test_template_id' => 'AmyBb6IW_AdP5OxcqBniqRdV5o7aUa36XJgphWOCZLs',
            'body' => [
                ['设备名称' => ['value' => '{{machine_name}}', 'field' => 'thing1']],
                ['设备编号' => ['value' => '{{machine_id}}', 'field' => 'character_string2']],
                ['最后在线时间' => ['value' => '{{last_online_time}}', 'field' => 'time4']],
            ],
        ],
        'mShipmentFailed' => [
            'template_type' => 'mShipmentFailed',
            'template_name' => '售货机出货失败通知',
            'display_name' => '售货机出货失败通知',
            'template_no' => '51500',
            'template_id' => 'xG3sxjGyzj0RBTi7RfsHZZNo73sr6W-raLTpTdx4Pp0',
            'test_template_id' => 'xG3sxjGyzj0RBTi7RfsHZZNo73sr6W-raLTpTdx4Pp0',
            'body' => [
                ['设备编号' => ['value' => '{{machine_id}}', 'field' => 'character_string1']],
                // 设备只上报故障码：订单编号字段复用为原始故障码。
                ['订单编号' => ['value' => '{{error_info}}', 'field' => 'character_string2']],
                // 商品名称字段复用为故障短名称。
                ['商品名称' => ['value' => '{{error_code}}', 'field' => 'thing3']],
                ['出货时间' => ['value' => '{{error_time}}', 'field' => 'time4']],
                // 故障上报不携带货道号，固定显示“-”。
                ['货道号' => ['value' => '{{channel_code}}', 'field' => 'character_string5']],
            ],
        ],
    ],

    'level_strategy_defaults' => [
        1 => [
            'level' => 1,
            'quiet_enabled' => 2,
            'quiet_start' => null,
            'quiet_end' => null,
            'interval_minutes' => 30,
            'day_limit' => 6,
        ],
        2 => [
            'level' => 2,
            'quiet_enabled' => 1,
            'quiet_start' => '22:00:00',
            'quiet_end' => '07:00:00',
            'interval_minutes' => 120,
            'day_limit' => 3,
        ],
        3 => [
            'level' => 3,
            'quiet_enabled' => 1,
            'quiet_start' => '22:00:00',
            'quiet_end' => '07:00:00',
            'interval_minutes' => 1440,
            'day_limit' => 1,
        ],
    ],
];
