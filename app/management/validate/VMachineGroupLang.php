<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:51
 */

namespace app\management\validate;


class VMachineGroupLang extends VCommon
{
    protected $rule = [
        "mgl_id" => "require",
        "mg_id" => "require",
        "mg_name" => "require|max:100",
        "desc" => "require|max:1024",
        "lang" => "require|max:10",
    ];

    protected $message = [
        "mgl_id.require" => "VMachineGroupLang.mgl_id_require",
        "mg_id.require" => "VMachineGroupLang.mg_id_require",
        "mg_name.require" => "VMachineGroupLang.mg_name_require",
        "mg_name.max" => "VMachineGroupLang.mg_name_max",
        "desc.require" => "VMachineGroupLang.desc_require",
        "desc.max" => "VMachineGroupLang.desc_max",
        "lang.require" => "VMachineGroupLang.lang_require",
        "lang.max" => "VMachineGroupLang.lang_max",
    ];

    protected $scene = [
        "add" => ["mg_id","mg_name","desc","lang"],
        "update" => ["mgl_id","mg_name","desc", "lang"],
        "del" => ["mgl_id"],
    ];
}