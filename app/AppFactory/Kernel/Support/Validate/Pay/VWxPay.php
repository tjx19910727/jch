<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:43
 */

namespace app\AppFactory\Kernel\Support\Validate\Pay;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VWxPay extends SupportValidate
{
    protected $rule = [

        "app_id" => 'require',
        "mch_id" => "require",
        "key" => "require",
        "serial" => "require",
        "cert_path" => "require|checkPath",
        "key_path" => "require|checkPath",
        "v3_key" => "require",
        "platform_serial" => "require",
        "platform_path" => "require|checkPath",
        "to_balance" => "require",
        "to_batches" => "require",
    ];

    protected $message = [

        "app_id.require" => "应用ID不能为空",
        "mch_id.require" => "商户ID不能为空",
        "key.require" => "支付密钥不能为空",
        "serial.require" => "证书编号不能为空",
        "cert_path.require" => "CERT证书路径不能为空",
        "key_path.require" => "Key证书路径不能为空",
        "v3_key.require" => "APIv3密钥不能为空",
        "platform_serial.require" => "平台证书编号不能为空",
        "platform_path.require" => "平台证书保存路径不能为空",
        "to_balance.require" => "付款至零钱状态不能为空",
        "to_batches.require" => "商家转账到零钱状态不能为空",
    ];

    protected $scene = [
        "wx" => ["app_id","mch_id","key","cert_path","key_path","v3_key","to_balance","to_batches"],
    ];

    public function checkPath($value)
    {
        if ($value && !file_exists(root_path() . "public" . $value)) {
            return "证书文件不存在";
        }
        return true;
    }
}