<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 16:01
 */

namespace app\management\validate;


class VGoodsCategory extends VCommon
{
    protected $rule = [
        "gc_id" => "require",
        "gc_name" => "require|max:100",
        "status" => "require",
    ];

    protected $message = [
        "gc_id.require" => "请选择商品分类",
        "gc_name.require" => "分类名称不能为空",
        "gc_name.max" => "分类名称长度超限制",
        "status.require" => "状态不能为空",
    ];

    protected $scene = [
        "add" => ["gc_name","status"],
        "update" => ["gc_id", "gc_name","status"],
    ];
}