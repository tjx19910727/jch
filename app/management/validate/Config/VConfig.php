<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 18:00
 */

namespace app\management\validate\Config;


use app\management\validate\VCommon;

class VConfig extends VCommon
{
    protected $rule = [
        "config_id" => "require",
        "config_name" => "require",
        "config_content" => "require",
        "config_switch" => "require",
    ];

    protected $message = [
        "config_id.require" => "VConfig.config_id_require",
        "config_name.require" => "VConfig.config_name_require",
        "config_content.require" => "VConfig.config_content_require",
        "config_switch.require" => "VConfig.config_switch_require",
    ];

    protected $scene = [
        "add" => ["config_name","config_content","config_switch"],
        "update" => ["config_id","config_name","config_content","config_switch"],
    ];
}