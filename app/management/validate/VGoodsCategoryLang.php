<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 16:20
 */

namespace app\management\validate;


class VGoodsCategoryLang extends VCommon
{
    protected $rule = [
        "gc_name" => "require|max:100",
        "gc_id" => "require",
        "lang" => "require",
    ];

    protected $message = [
        "gc_id.require" => "VGoodsCategoryLang.gc_id_require",
        "gc_name.require" => "VGoodsCategoryLang.gc_name_require",
        "gc_name.max" => "VGoodsCategoryLang.gc_name_max",
        "lang.require" => "VGoodsCategoryLang.lang_require",
    ];

    protected $scene = [
        "add" => ['gc_name',"gc_id","lang"],
        "update" => ['gcl_id'],
        "del" => ['gcl_id'],
    ];
}