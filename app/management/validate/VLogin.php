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
        "account.require" => "VLogin.account_require",
        "password.require" => "VLogin.password_require",
        "code.require" => "VLogin.code_require",
        "uniqid.require" => "VLogin.uniqid_require",
    ];

    protected $scene = [
        "login" => ["account","password","code","uniqid"],
    ];

}