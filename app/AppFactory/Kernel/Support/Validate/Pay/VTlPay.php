<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/12
 * Time: 9:41
 */

namespace app\AppFactory\Kernel\Support\Validate\Pay;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VTlPay extends SupportValidate
{
    protected $rule = [
        "app_id" => "require",
        "mch_id" => "require",
        "private_key" => "require",
        "public_key" => "require",
        "sm2_private_key" => "require",
        "sm2_public_key" => "require",
        "test" => "require|boolean",
    ];

    protected $message = [
        "app_id.require" => "app_id不能为空",
        "mch_id.require" => "mch_id不能为空",
        "private_key.require" => "私钥不能为空",
        "public_key.require" => "公钥不能为空",
        "sm2_private_key.require" => "sm2私钥不能为空",
        "sm2_public_key.require" => "sm2公钥不能为空",
        "test.require" => "运行模式不能为空",
    ];

    protected $scene = [
        "tl" => ['app_id',"mch_id","private_key","public_key","sm2_private_key","sm2_public_key","test"],
    ];
}