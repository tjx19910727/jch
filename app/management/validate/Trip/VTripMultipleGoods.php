<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:56
 */

namespace app\management\validate\Trip;


use app\management\validate\VCommon;

class VTripMultipleGoods extends VCommon
{

    protected $rule = [
        "tmg_id" => "require",
        "g_id" => "require",
        "is_required" => "require",
        "buy_lower" => "require|min:1",
        "buy_upper" => "require",
        "sale_amount" => "require",
        "rise_fall_ratio" => "require",
    ];

    protected $message = [
        "tmg_id.require" => "VTripMultiple.tmg_id_require",
        "g_id.require" => "VTripMultiple.g_id_require",
        "is_required.require" => "VTripMultiple.is_required_required",
        "buy_lower.require" => "VTripMultiple.buy_lower_required",
        "buy_lower.min" => "VTripMultiple.buy_lower_min",
        "buy_upper.require" => "VTripMultiple.buy_upper_required",
        "sale_amount.require" => "VTripMultiple.sale_amount_require",
    ];

    protected $scene = [
        "add" => ["g_id","is_required","buy_lower","buy_upper","sale_amount","rise_fall_ratio"],
        "update" => ["tmg_id"],
        "del" => ["tmg_id"],
    ];
}