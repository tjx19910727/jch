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
        "old_password" => "require",
        "new_password" => "require",
        "confirm_password" => "require",
        "code" => "require",
        "uniqid" => "require",
    ];

    protected $message = [
        "account.require" => "VLogin.account_require",
        "password.require" => "VLogin.password_require",
        "old_password.require" => "VLogin.old_password_require",
        "new_password.require" => "VLogin.new_password_require",
        "confirm_password.require" => "VLogin.confirm_password_require",
        "code.require" => "VLogin.code_require",
        "uniqid.require" => "VLogin.uniqid_require",
    ];

    protected $scene = [
        "login" => ["account","password","code","uniqid"],
        "changePassword" => ["account","old_password","new_password","confirm_password"],
    ];

}
