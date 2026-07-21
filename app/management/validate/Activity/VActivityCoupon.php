<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 14:30
 */

namespace app\management\validate\Activity;


use app\management\validate\VCommon;

class VActivityCoupon extends VCommon
{
    protected $rule = [
        "c_id" => "require",
        "c_name" => "require|max:100",
        "desc" => "max:255",
        "start_date" => "require",
        "end_date" => "require",
        "c_type" => "require",
        "designated_machine" => "require",
        "designated_goods" => "require",
        "url_day_count" => "integer|egt:0",
        "url_coupon_count" => "integer|gt:0",
    ];

    protected $message = [
        "c_id.require" => "VActivityCoupon.c_id_require",
        "c_name.require" => "VActivityCoupon.c_name_require",
        "c_name.max" => "VActivityCoupon.c_name_max",
        "desc.max" => "VActivityCoupon.desc_max",
        "start_date.require" => "VActivityCoupon.start_date_require",
        "end_date.require" => "VActivityCoupon.end_date_require",
        "c_type.require" => "VActivityCoupon.c_type_require",
        "designated_machine.require" => "VActivityCoupon.designated_machine_require",
        "designated_goods.require" => "VActivityCoupon.designated_goods_require",
        "url_day_count.integer" => "VActivityCoupon.url_day_count_integer",
        "url_day_count.egt" => "VActivityCoupon.url_day_count_egt",
        "url_coupon_count.integer" => "VActivityCoupon.url_coupon_count_integer",
        "url_coupon_count.gt" => "VActivityCoupon.url_coupon_count_gt",
    ];

    protected $scene = [
        "add" => ["c_name","desc","start_date","end_date","c_type","designated_machine","designated_goods","url_day_count","url_coupon_count"],
        "update" => ["c_id",'designated_machine','designated_goods',"url_day_count","url_coupon_count"],
        "del" => ["c_id"],
        "takeDown" => ["c_id"],
        "getUrl" => ["c_id"],
    ];
}