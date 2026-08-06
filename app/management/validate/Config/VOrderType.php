<?php

namespace app\management\validate\Config;

use app\management\validate\VCommon;

class VOrderType extends VCommon
{
    protected $rule = [
        "ot_id" => "require|number",
        "order_type" => "require|number",
        "order_type_name" => "require|max:50",
        "status" => "in:1,2",
        "sort" => "number",
        "remark" => "max:255",
    ];

    protected $message = [
        "ot_id.require" => "订单类型配置ID不能为空",
        "ot_id.number" => "订单类型配置ID必须为数字",
        "order_type.require" => "订单类型不能为空",
        "order_type.number" => "订单类型必须为数字",
        "order_type_name.require" => "订单类型名称不能为空",
        "order_type_name.max" => "订单类型名称不能超过50个字符",
        "status.in" => "状态不合法",
        "sort.number" => "排序必须为数字",
        "remark.max" => "备注不能超过255个字符",
    ];

    protected $scene = [
        "add" => ["order_type", "order_type_name", "status", "sort", "remark"],
        "del" => ["ot_id"],
    ];

    public function sceneUpdate()
    {
        return $this->only(["ot_id", "order_type", "order_type_name", "status", "sort", "remark"])
            ->append("ot_id", "require")
            ->remove("order_type", "require")
            ->remove("order_type_name", "require");
    }
}
