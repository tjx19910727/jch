<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 11:55
 */

namespace app\management\validate;


class VStrategyPayee extends VCommon
{
    protected $rule = [
        "sp_id" => "require",
        "sp_name" => 'require',
        "payee_type" => 'require|in:1,2,4,5,9',
        "app_id" => 'require',
        "mch_id" => "require",
        "content" => 'require',
        "status" => 'require',

        // 微信
        "key" => "require",
        "serial" => "require",
        "cert_path" => "require",
        "key_path" => "require",
        "v3_key" => "require",
        "platform_serial" => "require",
        "platform_path" => "require",
        "to_balance" => "require",
        "to_batches" => "require",

        // 支付宝
        "pid" => "require",
        "private_key_path" => "require",
        "ali_public_key_path" => "require",
        "ali_root_cert_path" => "require",
        "app_public_key_path" => "require",
        "aes_key" => "require",

        // 通联
        "private_key" => "require",
        "public_key" => "require",

        // 京东收银
        "agentNum" => "require",
        "customerNum" => "require",
        "shopNum" => "require",
        "accessKey" => "require",
        "secretKey" => "require",
    ];

    protected $message = [
        "sp_id.require" => "策略ID不能为空",
        "sp_name.require" => "策略名称不能为空",
        "payee_type.require" => "收款方类型不能为空",
        "payee_type.in" => "收款方类型不在限定范围内",
        "app_id.require" => "应用ID不能为空",
        "mch_id.require" => "商户ID不能为空",
        "content.require" => "配置内容不能为空",
        "status.require" => "状态不能为空",

        // 微信
        "key.require" => "支付密钥不能为空",
        "serial.require" => "证书编号不能为空",
        "cert_path.require" => "CERT证书路径不能为空",
        "key_path.require" => "Key证书路径不能为空",
        "v3_key.require" => "APIv3密钥不能为空",
        "platform_serial.require" => "平台证书编号不能为空",
        "platform_path.require" => "平台证书保存路径不能为空",
        "to_balance.require" => "付款至零钱状态不能为空",
        "to_batches.require" => "商家转账到零钱状态不能为空",

        // 通联
        "private_key.require" => "私钥不能为空",
        "public_key.require" => "公钥不能为空",

        // 支付宝
        "pid.require" => "商户账号（PID）不能为空",
        "private_key_path.require" => "私钥路径不能为空",
        "ali_public_key_path.require" => "支付宝平台公钥证书路径不能为空",
        "ali_root_cert_path.require" => "支付宝根证书路径不能为空",
        "app_public_key_path.require" => "应用公钥证书路径不能为空",
        "aes_key.require" => "AES密钥不能为空",

        // 京东收银
        "agentNum.require" => "代理商编号不能为空",
        "customerNum.require" => "商户编号不能为空",
        "shopNum.require" => "店铺编号不能为空",
        "accessKey.require" => "公钥不能为空",
        "secretKey.require" => "私钥不能为空",


    ];
    protected $scene = [
        "addSp" => ["sp_name","payee_type","app_id","content","status"],
        "addWx" => ["app_id","mch_id","key","cert_path","key_path","v3_key","to_balance","to_batches"],
        "addAli" => ["app_id","pid","private_key_path","ali_public_key_path","ali_root_cert_path","app_public_key_path","to_balance"],
        "addTl"  => ["app_id"],
        "addJdCashier" => ["agentNum","customerNum","shopNum","accessKey","secretKey"],
        "addTrip" => ["appId","appSecret","baseUrl"],
        "updateSp" => ['sp_id'],
        "addShopPoints" => ["app_id","publicKey","privateKey"]
    ];
}