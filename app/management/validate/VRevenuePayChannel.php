<?php

namespace app\management\validate;

class VRevenuePayChannel extends VCommon
{
    protected $rule = [
        "rpc_id" => "require",
        "pay_type" => "require|number",
        "payee_type" => "number",
        "channel_name" => "require",
        "settlement_type" => "number|in:1,2",
        "settlement_days" => "number|egt:0",
        "status" => "require|in:1,2",
    ];

    protected $message = [
        "rpc_id.require" => "分账渠道配置ID不能为空",
        "pay_type.require" => "订单支付类型不能为空",
        "pay_type.number" => "订单支付类型必须为数字",
        "payee_type.number" => "收款策略类型必须为数字",
        "channel_name.require" => "渠道名称不能为空",
        "settlement_type.in" => "分账时间类型不合法",
        "settlement_days.egt" => "T+N 天数不能小于0",
        "status.require" => "状态不能为空",
        "status.in" => "状态不合法",
    ];

    protected $scene = [
        "add" => ["pay_type", "channel_name"],
        "update" => ["rpc_id"],
    ];
}
