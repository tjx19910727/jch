<?php

namespace app\AppFactory\Kernel\Support\FaultNotice;

/**
 * 故障通知固定配置。
 *
 * 该类位于app目录，随应用代码一起发布，不依赖运维额外同步config文件。
 */
class FaultNoticeConfig
{
    public static function wechatTemplates()
    {
        return [
            'mFault' => [
                'template_type' => 'mFault',
                'template_name' => '设备异常提醒',
                'display_name' => '设备异常提醒（通用模板）',
                'template_no' => '43033',
                'template_id' => 'frqumju8oA7N8msUrhIiHpDd18j2Ie-DxLGlz5jWz8g',
                'test_template_id' => 'wpz0FL-2vY4biBErkD39WUGKIzMbkPmZPBHr2Beo9Go',
                'body' => [
                    ['设备编码' => ['value' => '{{machine_id}}', 'field' => 'character_string16']],
                    ['设备名称' => ['value' => '{{machine_name}}', 'field' => 'thing6']],
                    ['异常现象' => ['value' => '{{error_info}}', 'field' => 'thing12']],
                    ['异常时间' => ['value' => '{{error_time}}', 'field' => 'time15']],
                    // 模板字段名固定为“设备地址”，业务上填故障短名称，不填真实地址。
                    ['设备地址' => ['value' => '{{error_code}}', 'field' => 'thing9']],
                ],
            ],
            'mOffline' => [
                'template_type' => 'mOffline',
                'template_name' => '售货机离线通知',
                'display_name' => '售货机离线通知',
                'template_no' => '52646',
                'template_id' => 'AmyBb6IW_AdP5OxcqBniqRdV5o7aUa36XJgphWOCZLs',
                'test_template_id' => 'K5TJZWOaXezSF61DOhUxu0dGQsQqEuB34j8Mkw4PN5M',
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
                'test_template_id' => 'cw1oToF1jjkExKdSL1UpzPB8kjDBaofElVx1dC3T788',
                'body' => [
                    ['设备编号' => ['value' => '{{machine_id}}', 'field' => 'character_string1']],
                    ['订单编号' => ['value' => '{{trade_no}}', 'field' => 'character_string2']],
                    // 商品名称字段复用为故障短名称。
                    ['商品名称' => ['value' => '{{error_code}}', 'field' => 'thing3']],
                    ['出货时间' => ['value' => '{{error_time}}', 'field' => 'time4']],
                    // 货道号筛选未回写出货结果或已有失败数量的明细，按失败数量倒序取第一条。
                    ['货道号' => ['value' => '{{channel_code}}', 'field' => 'character_string5']],
                ],
            ],
        ];
    }

    public static function levelStrategyDefaults()
    {
        return [
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
        ];
    }
}
