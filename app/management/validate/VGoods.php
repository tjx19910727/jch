<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 16:01
 */

namespace app\management\validate;


class VGoods extends VCommon
{
    protected $rule = [
        "goods_id" => "require",
        "goods_name" => "require|max:100",
        "pic" => "max:100",
        "sell_by_date" => "max:6|number",
        "is_public" => "require",
        "status" => "require",
    ];

    protected $message = [
        "goods_id.require" => "请选择商品",
        "goods_name.require" => "商品名称不能为空",
        "goods_name.max" => "商品名称长度超限制",
        "pic.max" => "图片路径长度超限制",
        "sell_by_date.max" => "保质期长度超限制",
        "sell_by_date.number" => "保质期天数只能纯数字",
        "is_public.require" => "请确定是否为公共商品库商品",
        "status.require" => "请选择商品",
    ];

    protected $scene = [
        "add" => ["goods_name","pic","sell_by_date", "is_public","status"],
        "update" => ["goods_id", "goods_name","pic","sell_by_date", "is_public","status"],
    ];
}