<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/29
 * Time: 14:16
 */

return  [
    "init_payment_success" => "发起支付成功",
    "init_payment_fail" => "发起支付失败",

    "request_params_require" => "请求参数不能为空",

    "cancel_payment_success" => "撤销支付成功",
    "cancel_payment_fail" => "撤销支付失败",

    "StrategyPayee" => [
        "payee_config_no_json" => "收款方配置信息格式错误，不是JSON格式",
        "payee_config_no_data" => "查无收款方配置信息",
    ],
    "pay_type_not_in_scope" => "支付方式不在允许范围内",

    "VWx" => [
        "unKnow_code" =>  "",
    ],

    "VOrderPay" => [
        "order_no_data" => "查无订单信息",
        "machine_no_data" => "查无设备信息",
        "unKnow_auth_code" => "无法识别付款码类型",
        "unKnow_pay_type" => "未定义的支付类型",
        "update_order_pay_info_fail" => "修改订单支付信息失败",
        "pay_status3" => "订单已支付成功",
    ],

    "VJdCashier" => [

        "app_id_require" => "应用ID不能为空",
        "agentNum_require" => "代理商编号不能为空",
        "customerNum_require" => "商户编号不能为空",
        "shopNum_require" => "店铺编号不能为空",
        "accessKey_require" => "公钥不能为空",
        "secretKey_require" => "私钥不能为空",
        "bill_account_require" => "收款方分账账号不能为空",

    ],

    "VAliPay" => [
        "cert_not_exit" => "证书文件不存在",
        "app_id_require" => "应用ID不能为空",
        "pid_require" => "商户账号（PID）不能为空",
        "private_key_path_require" => "私钥路径不能为空",
        "ali_public_key_path_require" => "支付宝平台公钥证书路径不能为空",
        "ali_root_cert_path_require" => "支付宝根证书路径不能为空",
        "app_public_key_path_require" => "应用公钥证书路径不能为空",

    ],

    "VTrip" => [
        "appId_require" => "appId不能为空",
        "appSecret_require" => "appSecret不能为空",
        "baseUrl_require" => "请求地址不能为空",
    ],
];