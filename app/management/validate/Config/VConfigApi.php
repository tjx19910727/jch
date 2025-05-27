<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:26
 */

namespace app\management\validate\Config;


use app\management\validate\VCommon;

class VConfigApi extends VCommon
{

        protected $rule = [
            "id" => "require",
            "auth_name" => "require",
            "auth_password" => "require",
            "white_list" => "require",
        ];

        protected $message = [
            "id.require" => "id_require",
            "auth_name.require" => "auth_name_require",
            "auth_password.require" => "auth_password_require",
            "white_list.require" => "white_list_require",
        ];

        protected $scene = [
            "add" => ["auth_name","auth_password","white_list"],
            "getFind" => ["id"],
            "update" => ["id"],
            "del" => ["id"],
        ];
}