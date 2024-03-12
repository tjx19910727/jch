<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:56
 */

namespace app\machine\validate;


class VReceive extends VCommon
{
    protected $rule = [

        "machine_id" => "require",
        "msg_id" => "require|unique:machine_mq_record",
        "timestamp" => "require|checkTimestamp",
        "sign" => "require",

        "pay_type" => "require",
        "carList" => "require",

        "mcList" => "require",


        "operator" => "require",
        "repList" => "require",

        "mc_id" => "require",
        "g_id" => "require",
        "quantity" => "require",

        "adv_id" => "require",
        "play_time" => "require",

        "account" => "require",
        "password" => "require",
    ];

    protected $message = [

        "machine_id.require" => "VReceive.machine_id_require",
        "msg_id.require" => "VReceive.msg_id_require",
        "msg_id.unique" => "VReceive.msg_id_unique",
        "timestamp.require" => "VReceive.timestamp_require",
        "sign.require" => "VReceive.sign_require",

        "pay_type.require" => "VReceive.pay_type_require",
        "carList.require" => "VReceive.carList_require",

        "mcList.require" => "VReceive.mcList_require",

        "operator.require" => "VReceive.operator_require",
        "repList.require" => "VReceive.mcList_require",

        "mc_id.require" => "VReceive.mc_id_require",
        "g_id.require" => "VReceive.g_id_require",
        "quantity.require" => "VReceive.quantity_require",

        "adv_id.require" => "VAdvertisement.adv_id_require",
        "play_time.require" => "VAdvertisement.play_time_require",

        "account.require" => "VLogin.account_require",
        "password.require" => "VLogin.password_require",

    ];

    protected $scene = [
        "login" => ["msg_id","machine_id","timestamp","sign","account","password"],
        "logout" => ["msg_id","machine_id","timestamp","sign"],

        "getSystemInfo" => ["msg_id","machine_id","timestamp","sign"],
        "getMachine" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineChannel" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineGoods" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineInfo" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineConfig" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineHelp" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineView" => ["msg_id","machine_id","timestamp","sign"],
        "getMachineVersionPlan" => ["msg_id","machine_id","timestamp","sign"],

        "getGoods" => ["msg_id","machine_id","timestamp","sign"],
        "getAdv" => ["msg_id","machine_id","timestamp","sign"],
        "playAdv" => ["msg_id","machine_id","timestamp","sign","adv_id","play_time"],
        "subCar" => ["msg_id","machine_id","timestamp","sign","pay_type","carList"],
        "subChannel" => ["msg_id","machine_id","timestamp","sign"],

        "subMachineGoods" => ["msg_id","machine_id","timestamp","sign","mgList"],

        "replenishment" => ["msg_id","machine_id","timestamp","sign","operator","repList"],
        "changeChannelGoods" => ["msg_id","machine_id","timestamp","sign","mc_id","g_id","quantity"],
        "uploadMedia" => ["msg_id","machine_id","timestamp","sign"],

        "getCouponList" => ["msg_id","machine_id","timestamp","sign"],
        "getCoupon" => ["msg_id","machine_id","timestamp","sign"],


//        "carList" => ["mc_id","quantity"],
    ];

    public function checkTimestamp($item)
    {
//        if (!$item) return "时间戳不能为空";
//        if (time() - $item > 120) return "VReceive.timestamp_checkTimestamp_overdue";
        return true;
    }
}