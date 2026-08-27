<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/7
 * Time: 10:03
 */

namespace app\management\validate;


class VSaleOrders extends VCommon
{
    protected $rule = [
        "order_id" => "require",
        "machine_id" => "require|max:64",
        "refund" => "require",
        "payment_method" => "require",
        "payment_time" => "require",
        "payment_amount" => "require|float|gt:0",
        "receiver_account" => "require",
        "trade_no" => "max:64",
        "sod_id" => "integer|egt:0",
    ];

    protected $message = [
        "order_id.require" => "VSaleOrders.order_id_require",
        "machine_id.require" => "请选择打印小票的目标设备",
        "machine_id.max" => "目标设备编号长度不能超过64个字符",
        "refund.require" => "VSaleOrders.refund_require",
        "payment_method.require" => "VSaleOrders.offline_refund_payment_method_require",
        "payment_time.require" => "VSaleOrders.offline_refund_payment_time_require",
        "payment_amount.require" => "VSaleOrders.offline_refund_payment_amount_require",
        "payment_amount.float" => "VSaleOrders.offline_refund_payment_amount_invalid",
        "payment_amount.gt" => "VSaleOrders.offline_refund_payment_amount_invalid",
        "receiver_account.require" => "VSaleOrders.offline_refund_receiver_account_require",
        "trade_no.max" => "trade_no长度不能超过64个字符",
        "sod_id.integer" => "sod_id必须是整数",
        "sod_id.egt" => "sod_id不能小于0",
    ];

    protected $scene = [
        "refund" => ["order_id", "refund"],
        "printReceipt" => ["order_id", "machine_id"],
        "offlineRefund" => [
            "order_id",
            "refund",
            "payment_method",
            "payment_time",
            "payment_amount",
            "receiver_account",
        ],
        "manualPushToWeiCheng" => ["trade_no", "sod_id"],
        "markOutSuccess" => ["order_id"],
    ];
}
