<?php

namespace app\management\validate\Config;

use app\management\validate\VCommon;

class VPayType extends VCommon
{
    protected $rule = [
        "pt_id" => "require|number",
        "pay_type" => "require|number",
        "pay_type_name" => "require|max:50",
        "pay_scene" => "in:1,2",
        "status" => "in:1,2",
        "sort" => "number",
        "remark" => "max:255",
    ];

    protected $message = [
        "pt_id.require" => "支付类型配置ID不能为空",
        "pt_id.number" => "支付类型配置ID必须为数字",
        "pay_type.require" => "支付类型不能为空",
        "pay_type.number" => "支付类型必须为数字",
        "pay_type_name.require" => "支付类型名称不能为空",
        "pay_type_name.max" => "支付类型名称不能超过50个字符",
        "pay_scene.in" => "线上线下支付标记不合法",
        "status.in" => "状态不合法",
        "sort.number" => "排序必须为数字",
        "remark.max" => "备注不能超过255个字符",
    ];

    protected $scene = [
        "add" => ["pay_type", "pay_type_name", "pay_scene", "status", "sort", "remark"],
        "del" => ["pt_id"],
    ];

    public function sceneUpdate()
    {
        return $this->only(["pt_id", "pay_type", "pay_type_name", "pay_scene", "status", "sort", "remark"])
            ->append("pt_id", "require")
            ->remove("pay_type", "require")
            ->remove("pay_type_name", "require");
    }
}
