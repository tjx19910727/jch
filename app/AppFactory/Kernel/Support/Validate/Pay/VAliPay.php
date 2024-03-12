<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:43
 */

namespace app\AppFactory\Kernel\Support\Validate\Pay;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VAliPay extends SupportValidate
{
    protected $rule = [

        "app_id" => 'require',
        "pid" => "require",
        "private_key_path" => "require|checkPath",
        "ali_public_key_path" => "require|checkPath",
        "ali_root_cert_path" => "require|checkPath",
        "app_public_key_path" => "require|checkPath",
    ];

    protected $message = [

        "app_id.require" => "VAliPay.app_id_require",
        "pid.require" => "VAliPay.pid_require",
        "private_key_path.require" => "VAliPay.private_key_path_require",
        "ali_public_key_path.require" => "VAliPay.ali_public_key_path_require",
        "ali_root_cert_path.require" => "VAliPay.ali_root_cert_path_require",
        "app_public_key_path.require" => "VAliPay.app_public_key_path_require",
    ];

    protected $scene = [
        "ali" => ["app_id","pid","private_key_path","ali_public_key_path","ali_root_cert_path","app_public_key_path"],
    ];

    public function checkPath($value)
    {
        if (!file_exists(root_path() . "public/" . $value)) {
            return "VAliPay.cert_not_exit";
        }
        return true;
    }
}