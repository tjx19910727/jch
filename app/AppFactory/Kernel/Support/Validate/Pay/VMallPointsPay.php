<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/14
 * Time: 13:50
 */

namespace app\AppFactory\Kernel\Support\Validate\Pay;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VMallPointsPay extends SupportValidate
{

        protected $rule = [
            "app_id" => "require",
            "publicKey" => "require",
            "privateKey" => "require",
        ];

        protected $message = [
            "app_id.require" => "VMallPointsPay.appId_require",
            "publicKey.require" => "VMallPointsPay.appSecret_require",
            "privateKey.require" => "VMallPointsPay.baseUrl_require",
        ];

        protected $scene = [
            "mallPointsPay" => ["app_id","publicKey","privateKey"],
        ];
}