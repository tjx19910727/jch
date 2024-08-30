<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 10:57
 */

namespace app\machine\validate;


class VHotel extends VCommon
{

    protected $rule = [
        "machine_id" => "require",
        "msg_id" => "require|unique:machine_mq_record",
        "timestamp" => "require|checkTimestamp",
        "sign" => "require",

        "cityId" => "require",
        "hotelId" => "require",
        "checkInDate" => "require",
        "checkOutDate" => "require",
        "pageNum" => "require",
        "page" => "require",

        "count" => "require",
        "quantity" => "require",
        "tripData" => "require",
        "nightlyPrice" => "require",
    ];

    protected $message = [
        "machine_id.require" => "VReceive.machine_id_require",
        "msg_id.require" => "VReceive.msg_id_require",
        "msg_id.unique" => "VReceive.msg_id_unique",
        "timestamp.require" => "VReceive.timestamp_require",
        "sign.require" => "VReceive.sign_require",

        "cityId.require" => "VHotel.cityId_require",
        "hotelId.require" => "VHotel.hotelId_require",
        "checkInDate.require" => "VHotel.checkInDate_require",
        "checkOutDate.require" => "VHotel.checkOutDate_require",
        "pageNum.require" => "VHotel.pageNum_require",
        "page.require" => "VHotel.page_require",

        "hotelList.require" => "VReceive.hotelList_require",

        "count.require" => "VHotel.count_require",
        "quantity.require" => "VHotel.quantity_require",
        "tripData.require" => "VHotel.tripData_require",
        "nightlyPrice.require" => "VHotel.nightlyPrice_require",
    ];

    protected $scene = [
        "getTripCity" => ["pageNum", "page"],
        "getList" => ["cityId","quantity", "checkInDate", "checkOutDate", "pageNum", "page"],
        "getDetails" => ["hotelId"],
        "getRoomList" => ["hotelId","quantity", "checkInDate", "checkOutDate"],
        "subHotel" => ["msg_id", "machine_id", "timestamp", "sign", "order_id", "hotelList"],
        "hotel" => ["hotelId", "roomId", "totalPrice", "pay_amount", "checkInDate", "checkOutDate", "guestNames"],
        "availableCheck" => ["machine_id","hotelId","roomId", "count", "quantity", "checkInDate", "checkOutDate", "tripData","nightlyPrice"],
    ];

    public function checkTimestamp($item)
    {
//        if (!$item) return "时间戳不能为空";
//        if (time() - $item > 120) return "VReceive.timestamp_checkTimestamp_overdue";
        return true;
    }
}