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
            "cityId" => "require",
            "hotelId" => "require",
            "checkInDate" => "require",
            "checkOutDate" => "require",
            "pageNum" => "require",
            "page" => "require",
        ];

        protected $message = [
            "cityId.require" => "VHotel.cityId_require",
            "hotelId.require" => "VHotel.hotelId_require",
            "checkInDate.require" => "VHotel.checkInDate_require",
            "checkOutDate.require" => "VHotel.checkOutDate_require",
            "pageNum.require" => "VHotel.pageNum_require",
            "page.require" => "VHotel.page_require",
        ];

        protected $scene = [
            "getTripCity" => ["pageNum","page"],
            "getList" => ["cityId","checkInDate","checkOutDate","pageNum","page"],
            "getDetailsList" => ["hotelId"],
            "getRoomList" => ["hotelId","checkInDate","checkOutDate"],
        ];
}