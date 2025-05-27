<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/8
 * Time: 16:05
 */

namespace app\management\validate;


class VStrategyIncome extends VCommon
{
    protected $rule = [
        "si_id" => " require",
        "income_name" => " require",
        "income_value" => " require|between:0,100",
        "status" => " require",
    ];

    protected $message = [
        "si_id.require" => "策略ID不能为空",
        "income_name.require" => "分润策略名称不能为空",
        "income_value.require" => "分润比例不能为空",
        "income_value.between" => "分润比例不允许超过范围",
        "status.require" => "状态不能为空",
    ];

    protected $scene = [
        "addSi" => ["income_name","income_value","status"],
        "updateSi" => ["si_id"],
    ];
}