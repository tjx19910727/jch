<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:27
 */

namespace app\management\validate;


class VTemplatePlugins extends VCommon
{

    protected $rule = [
        "id" => "require",
        "plugin_name" => "require",
        "display_name" => "require",
        "type" => "require",
    ];

    protected $message = [
        "id.require" => "VTemplatePlugins.id_require",
        "plugin_name.require" => "VTemplatePlugins.plugin_name_require",
        "display_name.require" => "VTemplatePlugins.display_name_require",
        "type.require" => "VTemplatePlugins.type_require",
    ];

    protected $scene = [
        "add" => ["plugin_name","display_name","type"],
        "update" => ["id"],
        "del" => ["id"],
    ];
}