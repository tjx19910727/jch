<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:37
 */

namespace app\management\validate\Email;


use app\management\validate\VCommon;

class VEmailConfig extends VCommon
{

        protected $rule = [
            "ec_id" => "require",
            "host" => "require",
            "username" => "require",
            "authCode" => "require",
            "sendEmail" => "require",
        ];

        protected $message = [
            "ec_id.require" => "VEmailConfig.ec_id_require",
            "host.require" => "VEmailConfig.host_require",
            "username.require" => "VEmailConfig.username_require",
            "authCode.require" => "VEmailConfig.authCode_require",
            "sendEmail.require" => "VEmailConfig.sendEmail_require",
        ];

        protected $scene = [
            "add" => ['host','username','authCode','sendEmail'],
            "update" => ['ec_id'],
            "del" => ['ec_id'],
        ];
}