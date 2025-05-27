<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:56
 */

namespace app\management\validate\Trip;


use app\management\validate\VCommon;

class VTripMultipleHotel extends VCommon
{

    protected $rule = [
        "tmh_id" => "require",
        "tc_id" => "require",
        "cityId" => "require",
        "cityName" => "require",
        "hotelId" => "require",
        "hotelName" => "require",
    ];

    protected $message = [
        "tmh_id.require" => "VTripMultiple.tmh_id_require",
        "tc_id.require" => "VTripMultiple.tc_id_require",
        "cityId.require" => "VTripMultiple.cityId_require",
        "cityName.require" => "VTripMultiple.cityName_require",
        "hotelId.require" => "VTripMultiple.hotelId_require",
        "hotelName.require" => "VTripMultiple.hotelName_require",
    ];

    protected $scene = [
        "add" => ["tc_id","cityId","cityName","hotelId","hotelName"],
        "update" => ["tmh_id"],
        "del" => ["tmh_id"],
    ];
}