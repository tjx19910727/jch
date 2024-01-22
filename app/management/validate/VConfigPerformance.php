<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/17
 * Time: 17:22
 */

namespace app\management\validate;


class VConfigPerformance extends VCommon
{
    protected $rule = [
        "cp_id" => "require",
        "name" => "require|max:100",
        "field" => "require|max:50|unique:config_performance",
        "lang" => "require|max:100",
    ];

    protected $message = [
        "cp_id.require" => "VConfig.cp_id_require",
        "name.require" => "VConfig.name_require",
        "name.max" => "VConfig.name_max",
        "field.require" => "VConfig.field_require",
        "field.max" => "VConfig.filed_max",
        "field.unique" => "VConfig.filed_unique",
        "lang.require" => "VConfig.lang_require",
        "lang.max" => "VConfig.lang_max",
    ];

    protected $scene = [
        "add" => ["name","field","lang"],
        "update" => ["cp_id","name","field","lang"],
    ];
}