<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 16:01
 */

namespace app\management\validate;


class VGoodsLang extends VCommon
{
    protected $rule = [
        "gl_id" => "require",
        "g_id" => "require",
        "g_name" => "require|max:100",
        "manufacturer" => "max:100",
        "lang" => "require",
    ];

    protected $message = [
        "gl_id.require" => "VGoodsLang.gl_id_require",
        "g_id.require" => "VGoodsLang.g_id_require",
        "g_name.require" => "VGoodsLang.g_name_require",
        "g_name.max" => "VGoodsLang.g_name_max",
        "manufacturer.max" => "VGoodsLang.manufacturer_max",
        "lang.require" => "VGoodsLang.lang_require",
    ];

    protected $scene = [
        "add" => ["g_id","g_name","manufacturer"],
        "update" => ["gl_id"],
        "getList" => ["g_id","lang"],
        "del" => ['gl_id'],
    ];

//    public function sceneUpdate()
//    {
//        return self::only(["gl_id","g_id","g_name","manufacturer"])
//            ->remove("g_name","require");
//    }
}