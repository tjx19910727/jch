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
        "refund" => "require",
        "payment_method" => "require",
        "payment_time" => "require",
        "payment_amount" => "require|float|gt:0",
        "receiver_account" => "require",
    ];

    protected $message = [
        "order_id.require" => "VSaleOrders.order_id_require",
        "refund.require" => "VSaleOrders.refund_require",
        "payment_method.require" => "VSaleOrders.offline_refund_payment_method_require",
        "payment_time.require" => "VSaleOrders.offline_refund_payment_time_require",
        "payment_amount.require" => "VSaleOrders.offline_refund_payment_amount_require",
        "payment_amount.float" => "VSaleOrders.offline_refund_payment_amount_invalid",
        "payment_amount.gt" => "VSaleOrders.offline_refund_payment_amount_invalid",
        "receiver_account.require" => "VSaleOrders.offline_refund_receiver_account_require",
    ];

    protected $scene = [
        "refund" => ["order_id", "refund"],
        "offlineRefund" => [
            "order_id",
            "refund",
            "payment_method",
            "payment_time",
            "payment_amount",
            "receiver_account",
        ],
    ];
}