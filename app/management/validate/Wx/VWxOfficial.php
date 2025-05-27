<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 10:17
 */

namespace app\management\validate\Wx;


use app\management\validate\VCommon;

class VWxOfficial extends VCommon
{

    protected $rule = [
        "id" => "require",
        "gh_id" => "require",
        "wx_name" => "require",
        "app_id" => "require",
        "secret" => "require",
        "token" => "require",
        "aes_key" => "require",
        "wx_txt" => "require",
    ];

    protected $message = [
        "id.require" => "VWxOfficial.id_require",
        "gh_id.require" => "VWxOfficial.gh_id_require",
        "wx_name.require" => "VWxOfficial.wx_name_require",
        "app_id.require" => "VWxOfficial.app_id_require",
        "secret.require" => "VWxOfficial.secret_require",
        "token.require" => "VWxOfficial.token_require",
        "aes_key.require" => "VWxOfficial.aes_key_require",
        "wx_txt.require" => "VWxOfficial.wx_txt_require",
    ];

    protected $scene = [
        "add" => ['app_id','secret','token','aes_key','wx_txt'],
        "update" => ["id"],
        "del" => ["id"],
    ];
}