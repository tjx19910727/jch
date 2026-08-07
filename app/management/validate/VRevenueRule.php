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
        "coupon_id" => "number",
        "cost_assume" => "in:0,1,2",
        "trigger_pay_types" => "array",
        "sort" => "number",
        "base_type" => "in:1,2",
        "turnover_type" => "in:1,2",
        "tier_calc_mode" => "in:1,2",
    ];

    protected $message = [
        "rrcfg_id.require" => "分账配置ID不能为空",
        "config_name.require" => "分账配置名称不能为空",
        "rule_mode.require" => "分账模式不能为空",
        "rule_mode.in" => "分账模式不合法",
        "receiver_config.require" => "分账接收方配置不能为空",
        "settlement_type.in" => "分账时间类型不合法",
        "settlement_days.number" => "T+N分账天数必须为数字",
        "coupon_id.number" => "优惠券ID必须为数字",
        "cost_assume.in" => "优惠券成本承担方式不合法",
        "trigger_pay_types.array" => "触发收款方式必须为数组",
        "sort.number" => "排序必须为数字",
        "base_type.in" => "分账基数类型不合法",
        "turnover_type.in" => "阶梯营业额口径不合法",
        "tier_calc_mode.in" => "阶梯计算模式不合法",
    ];

    protected $scene = [
        "saveConfig" => ["config_name", "rule_mode"],
        "saveScope" => ["rrcfg_id"],
    ];
}
