<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:54
 */

namespace app\management\validate\Trip;


use app\management\validate\VCommon;

class VTripMultiple extends VCommon
{

        protected $rule = [
            "tm_id" => "require",
            "tm_name" => "require",
            "status" => "require",
            "designated_hotel" => "require",
            "designated_goods" => "require",
            "designated_machine" => "require",
            "rise_fall_ratio" => "require",
//            "hotelList" => "require",
//            "goodsList" => "require",
            "machineList" => "require",
        ];

        protected $message = [
            "tm_id.require" => "VTripMultiple.tm_id_require",
            "tm_name.require" => "VTripMultiple.tm_name_require",
            "status.require" => "VTripMultiple.status_require",
            "designated_hotel.require" => "VTripMultiple.designated_hotel_require",
            "designated_goods.require" => "VTripMultiple.designated_goods_require",
            "designated_machine.require" => "VTripMultiple.designated_machine_require",
            "machineList.require" => "VTripMultiple.machineList_require",
        ];

        protected $scene = [
            "add" => ["tm_name","status","designated_hotel","designated_goods","designated_machine","machineList"],
            "update" => ["tm_id"],
            "del" => ["tm_id"],
        ];
}