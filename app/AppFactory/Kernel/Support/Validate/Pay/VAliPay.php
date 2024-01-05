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

        "app_id.require" => "应用ID不能为空",
        "pid.require" => "商户账号（PID）不能为空",
        "private_key_path.require" => "私钥路径不能为空",
        "ali_public_key_path.require" => "支付宝平台公钥证书路径不能为空",
        "ali_root_cert_path.require" => "支付宝根证书路径不能为空",
        "app_public_key_path.require" => "应用公钥证书路径不能为空",
    ];

    protected $scene = [
        "ali" => ["app_id","pid","private_key_path","ali_public_key_path","ali_root_cert_path","app_public_key_path"],
    ];

    public function checkPath($value)
    {
        if (!file_exists(root_path() . "public" . $value)) {
            return "证书文件不存在";
        }
        return true;
    }
}