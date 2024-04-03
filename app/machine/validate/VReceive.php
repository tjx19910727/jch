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

        "manager_id" => "require",


        "pay_method" => "require",
        "total_price" => "require",
        "total_quantity" => "require",
        "alc_id" => "require",

        "order_id" => "require",
        "fd_id" => "require",

    ];

    protected $message = [

        "machine_id.require" => "VReceive.machine_id_require",
        "msg_id.require" => "VReceive.msg_id_require",
        "msg_id.unique" => "VReceive.msg_id_unique",
        "timestamp.require" => "VReceive.timestamp_require",
        "sign.require" => "VReceive.sign_require",

        "pay_type.require" => "VReceive.pay_type_require",
        "pay_method.require" => "VReceive.pay_method_require",
        "total_price.require" => "VReceive.total_price_require",
        "total_quantity.require" => "VReceive.total_quantity_require",
        "alc_id.require" => "VReceive.alc_id_require",

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

        "manager_id.require" => "VReceive.manager_id_require",

        "order_id.require" => "VReceive.order_id_require",
        "fd_id.require" => "VReceive.fd_id_require",

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
        "subCar" => ["msg_id","machine_id","timestamp","sign","pay_type","pay_method"],
        "subChannel" => ["msg_id","machine_id","timestamp","sign"],

        "subMachineGoods" => ["msg_id","machine_id","timestamp","sign","mgList"],

        "replenishment" => ["msg_id","machine_id","timestamp","sign","operator","repList"],
        "changeChannelGoods" => ["msg_id","machine_id","timestamp","sign","mc_id","mg_id","quantity"],
        "uploadMedia" => ["msg_id","machine_id","timestamp","sign"],

        "getCouponList" => ["msg_id","machine_id","timestamp","sign"],
        "getCoupon" => ["msg_id","machine_id","timestamp","sign"],

        "getPickList" => ["msg_id","machine_id","timestamp","sign"],
        "getPick" => ["msg_id","machine_id","timestamp","sign"],

        "getCheckStockImg" => ["msg_id","machine_id","timestamp","sign","manager_id"],

        "getGoodsFind" => ["msg_id","machine_id","timestamp","sign","g_id"],

        "getLotteryList" => ["msg_id","machine_id","timestamp","sign"],

        "getLotteryOrder" => ["msg_id","machine_id","timestamp","sign","pay_type","pay_method","total_price","total_quantity","alc_id"],
        "getLuckyDraw" => ["msg_id","machine_id","timestamp","sign","order_id"],

        "getLotteryOutGoods" => ["msg_id","machine_id","timestamp","sign","order_id"],

        "getFd" => ["msg_id","machine_id","timestamp","sign"],

        "useFd" => ["msg_id","machine_id","timestamp","sign","order_id","fd_id"],

    ];

    public function checkTimestamp($item)
    {
//        if (!$item) return "时间戳不能为空";
//        if (time() - $item > 120) return "VReceive.timestamp_checkTimestamp_overdue";
        return true;
    }
}