<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/21
 * Time: 10:05
 */

namespace app\management\validate;


class VHotel extends VCommon
{

        protected $rule = [
            "page" => "require",
            "pageNum" => "require",
            "cityId" => "require",
        ];

        protected $message = [
            "cityId.require" => "VHotel.cityId_require",
            "page.require" => "VHotel.page_require",
            "pageNum.require" => "VHotel.pageNum_require",
        ];

        protected $scene = [
            "getHotelList" => ["cityId","page","pageNum"],
            "getRoomList" => ["hotelId"],
        ];
}