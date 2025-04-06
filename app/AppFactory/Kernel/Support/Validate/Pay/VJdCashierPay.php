<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/29
 * Time: 13:45
 */

namespace app\AppFactory\Kernel\Support\Validate\Pay;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VJdCashierPay extends SupportValidate
{
    protected $rule = [
        "app_id" => "require",
        "agentNum" => "require",
        "customerNum" => "require",
        "shopNum" => "require",
        "accessKey" => "require",
        "secretKey" => "require",
        "bill_account" => "require",
    ];
    protected $message = [
        "app_id.require" => "VJdCashier.app_id_require",
        "agentNum.require" => "VJdCashier.agentNum_require",
        "customerNum.require" => "VJdCashier.customerNum_require",
        "shopNum.require" => "VJdCashier.shopNum_require",
        "accessKey.require" => "VJdCashier.accessKey_require",
        "secretKey.require" => "VJdCashier.secretKey_require",
        "bill_account.require" => "VJdCashier.bill_account_require",
    ];
    protected $scene = [
        "jdPay" => ['agentNum','customerNum','shopNum','accessKey','secretKey'],
    ];
}