<?php

namespace app\management\validate;

class VRevenuePayChannel extends VCommon
{
    protected $rule = [
        "rpc_id" => "require",
        "pay_type" => "require|number",
        "channel_name" => "require",
        "status" => "require|in:1,2",
    ];

    protected $message = [
        "rpc_id.require" => "分账渠道配置ID不能为空",
        "pay_type.require" => "订单支付类型不能为空",
        "pay_type.number" => "订单支付类型必须为数字",
        "channel_name.require" => "渠道名称不能为空",
        "status.require" => "状态不能为空",
        "status.in" => "状态不合法",
    ];

    protected $scene = [
        "add" => ["pay_type", "channel_name"],
        "update" => ["rpc_id"],
    ];
}
