<?php

namespace app\management\validate;

class VRevenueRule extends VCommon
{
    protected $rule = [
        "rrcfg_id" => "require",
        "config_name" => "require",
        "rule_mode" => "require|in:1,2,3,4,5",
        "receiver_config" => "require",
        "settlement_type" => "in:1,2",
        "settlement_days" => "number",
        "discount_type" => "in:0,1,2",
        "discount_value" => "number",
        "sort" => "number",
        "base_type" => "in:1,2",
        "turnover_type" => "in:1,2",
        "tier_calc_mode" => "in:1,2",
        "coupon_code" => "regex:/^[1-9][0-9]{5}$/",
    ];

    protected $message = [
        "rrcfg_id.require" => "分账配置ID不能为空",
        "config_name.require" => "分账配置名称不能为空",
        "rule_mode.require" => "分账模式不能为空",
        "rule_mode.in" => "分账模式不合法",
        "receiver_config.require" => "分账接收方配置不能为空",
        "settlement_type.in" => "分账时间类型不合法",
        "settlement_days.number" => "T+N分账天数必须为数字",
        "discount_type.in" => "优惠方式不合法",
        "discount_value.number" => "优惠金额或比例必须为数字",
        "sort.number" => "排序必须为数字",
        "base_type.in" => "分账基数类型不合法",
        "turnover_type.in" => "阶梯营业额口径不合法",
        "tier_calc_mode.in" => "阶梯计算模式不合法",
        "coupon_code.regex" => "优惠券编码必须为非0开头的6位数字",
    ];

    protected $scene = [
        "saveConfig" => ["config_name", "rule_mode"],
        "saveScope" => ["rrcfg_id"],
    ];
}
