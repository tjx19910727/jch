<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/6
 * Time: 13:50
 */

namespace app\AppFactory\Kernel\Support\Validate\Pay;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VTrip extends SupportValidate
{

        protected $rule = [
            "appId" => "require",
            "appSecret" => "require",
            "baseUrl" => "require",
        ];

        protected $message = [
            "appId.require" => "VTrip.appId_require",
            "appSecret.require" => "VTrip.appSecret_require",
            "baseUrl.require" => "VTrip.baseUrl_require",
        ];

        protected $scene = [
            "tripPay" => ["appId","appSecret"],
        ];
}