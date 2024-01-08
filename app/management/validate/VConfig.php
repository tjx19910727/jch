<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 18:00
 */

namespace app\management\validate;


class VConfig extends VCommon
{
    protected $rule = [
        "config_id" => "require",
        "config_name" => "require",
        "config_content" => "require",
        "config_switch" => "require",

        // 萤石开放平台
        "appKey" => "require",
        "appSecret" => "require",

        // 微信开放平台
        "app_id" => "require",
        "secret" => "require",
        "token" => "require",
        "aes_key" => "require",

    ];
    protected $message = [
        "config_id.require" => "配置ID不能为空",
        "config_name.require" => "配置名称不能为空",
        "config_content.require" => "配置内容不能为空",
        "config_switch.require" => "配置开关不能为空",

        // 萤石开放平台
        "appKey.require" => "appKey不能为空",
        "appSecret.require" => "appSecret不能为空",

        // 微信开放平台
        "app_id.require" => "app_id不能为空",
        "secret.require" => "secret不能为空",
        "token.require" => "token不能为空",
        "aes_key.require" => "aes_key不能为空",

    ];
    protected $scene = [
        "add" => ["config_name","config_content","config_switch"],
        "update" => ["config_id","config_name","config_content","config_switch"],

        "fluorite" => ["appKey","appSecret"],
        "openPlatform" => ["app_id","secret","token","aes_key"],
    ];
}