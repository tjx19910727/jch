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
        "gc_id.require" => "VGoodsCategory.gc_id_require",
        "gc_name.require" => "VGoodsCategory.gc_name_require",
        "gc_name.max" => "VGoodsCategory.gc_name_max",
        "status.require" => "VGoodsCategory.status_require",
    ];

    protected $scene = [
        "add" => ["gc_name"],
        "update" => ["gc_id"],
    ];
}