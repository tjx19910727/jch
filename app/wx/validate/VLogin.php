<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/25
 * Time: 16:35
 */

namespace app\wx\validate;


class VLogin extends VCommon
{
    protected $rule = [
        "time" => "require",
        "login_id" => "require",
    ];

    protected $message = [
        "time.require" => "Login.time_require",
        "login_id.require" => "Login.login_id_require",
    ];

    protected $scene = [
        "scanLogin" => ["login_id","time"],
    ];
}