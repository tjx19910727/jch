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
        "timestamp" => "require|checkTimestamp",
        "sign" => "require",

        "pay_type" => "require",
        "carList" => "require",

        "mc_id" => "require",
        "quantity" => "require",

    ];

    protected $message = [

        "machine_id.require" => "VReceive.machine_id_require",
        "timestamp.require" => "VReceive.timestamp_require",
        "sign.require" => "VReceive.sign_require",

        "pay_type.require" => "VReceive.pay_type_require",
        "carList.require" => "VReceive.carList_require",

        "mc_id.require" => "VReceive.mc_id_require",
        "quantity.require" => "VReceive.quantity_require",

    ];

    protected $scene = [
        "getMachine" => ["machine_id","timestamp","sign"],
        "getMachineChannel" => ["machine_id","timestamp","sign"],
        "getMachineGoods" => ["machine_id","timestamp","sign"],
        "getMachineInfo" => ["machine_id","timestamp","sign"],
        "getMachineConfig" => ["machine_id","timestamp","sign"],
        "getMachineHelp" => ["machine_id","timestamp","sign"],
        "getGoods" => ["machine_id","timestamp","sign"],
        "getAdv" => ["machine_id","timestamp","sign"],
        "subCar" => ["machine_id","timestamp","sign","pay_type","carList"],
    ];

    public function checkTimestamp($item)
    {
//        if (!$item) return "时间戳不能为空";
//        if (time() - $item > 120) return "VReceive.timestamp_checkTimestamp_overdue";
        return true;
    }
}