<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 15:16
 */

namespace app\management\validate;


class VLogin extends VCommon
{
    protected $rule = [
        "account" => "require",
        "password" => "require",
        "code" => "require",
        "uniqid" => "require",
    ];

    protected $message = [
        "account.require" => "账号不能为空",
        "password.require" => "密码不能为空",
        "code.require" => "验证码不能为空",
        "uniqid.require" => "验证码UUID不能为空",
    ];

    protected $scene = [
        "login" => ["account","password","code"],
    ];

}