<?php

namespace app\management\validate;

class VRevenueRule extends VCommon
{
    protected $rule = [
        "rr_id" => "require",
        "rri_id" => "require",
        "rrit_id" => "require",
        "rule_name" => "require",
        "rule_mode" => "require|in:1,2,3,4",
        "g_id" => "require",
        "receiver_ao_id" => "require",
        "ra_id" => "require",
        "manager_id" => "require",
        "calc_type" => "require|in:1,2,3,4",
        "calc_value" => "number",
        "threshold_min" => "require|number",
        "threshold_max" => "number",
        "settlement_type" => "in:1,2",
        "settlement_days" => "number",
        "status" => "require",
    ];

    protected $message = [
        "rr_id.require" => "分账策略ID不能为空",
        "rri_id.require" => "分账策略明细ID不能为空",
        "rrit_id.require" => "阶梯分账明细ID不能为空",
        "rule_name.require" => "分账策略名称不能为空",
        "rule_mode.require" => "分账策略模式不能为空",
        "rule_mode.in" => "分账策略模式不合法",
        "g_id.require" => "商品ID不能为空",
        "receiver_ao_id.require" => "分账接收组织不能为空",
        "ra_id.require" => "分账账户不能为空",
        "manager_id.require" => "账户管理人不能为空",
        "calc_type.require" => "计算方式不能为空",
        "calc_type.in" => "计算方式不合法",
        "calc_value.number" => "分账比例或金额必须为数字",
        "threshold_min.require" => "营业额下限不能为空",
        "threshold_min.number" => "营业额下限必须为数字",
        "threshold_max.number" => "营业额上限必须为数字",
        "settlement_type.in" => "分账时间类型不合法",
        "settlement_days.number" => "T+N分账天数必须为数字",
        "status.require" => "状态不能为空",
    ];

    protected $scene = [
        "add" => ["rule_name", "rule_mode"],
        "update" => ["rr_id"],
        "addItem" => ["rr_id", "receiver_ao_id", "ra_id", "manager_id", "calc_type"],
        "updateItem" => ["rri_id"],
        "addProductItem" => ["rr_id", "g_id", "receiver_ao_id", "ra_id", "manager_id", "calc_type", "calc_value"],
        "addTier" => ["rri_id", "threshold_min", "calc_value"],
        "updateTier" => ["rrit_id"],
    ];
}
