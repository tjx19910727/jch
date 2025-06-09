<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/6/7
 * Time: 15:23
 */

namespace app\pay\validate;


class VPos extends VCommon
{
    protected $rule = [
        "msg_id" => "require|unique:machine_mq_record",
        "timestamp" => "require|checkTimestamp",
        "sign" => "require",

        "payment_status" => "require",
        "trade_no" => "require",
        "mch_no" => "require",
        "payment_type" => "require",
        "machine_id" => "require",
    ];
    protected $message = [
        "msg_id.require" => "VPos.msg_id_require",
        "msg_id.unique" => "VPos.msg_id_unique",
        "timestamp.require" => "VPos.timestamp_require",
        "sign.require" => "VPos.sign_require",

        "machine_id.require" => "VPos.pay_status_require",
        "payment_status.require" => "VPos.pay_status_require",
        "trade_no.require" => "VPos.trade_no_require",
        "mch_no.require" => "VPos.mch_no_require",
        "payment_type.require" => "VPos.payment_type_require",
    ];
    protected $scene = [
        "posNotify" => ["msg_id","machine_id","payment_type","payment_status","trade_no","mch_no"],
    ];


    public function checkTimestamp($item)
    {
        if (!$item) return "时间戳不能为空";
        if (time() - $item > 120) return "VReceive.timestamp_checkTimestamp_overdue";
        return true;
    }
}