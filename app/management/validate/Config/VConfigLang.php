<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 14:17
 */

namespace app\management\validate\Config;

use app\management\validate\VCommon;

class VConfigLang extends VCommon
{
    protected $rule = [
        "l_id" => "require",
        "name" => "require",
        "lang" => "require",
    ];

    protected $message = [
        "l_id.require" => "VConfig.l_id_require",
        "name.require" => "VConfig.name_require",
        "lang.require" => "VConfig.lang_require",
    ];

    protected $scene = [
        "add" => ["name","lang"],
        "update" => ["l_id"],
        "del" => ["l_id"],
    ];
}