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
        "slogan" => "max:20",
        "start_date" => "require",
        "end_date" => "require",
        "c_type" => "require",
        "designated_machine" => "require",
        "designated_goods" => "require",
        "url_day_count" => "integer|egt:0",
        "url_coupon_count" => "integer|gt:0",
        "need_oauth" => "in:0,1",
    ];

    protected $message = [
        "c_id.require" => "VActivityCoupon.c_id_require",
        "c_name.require" => "VActivityCoupon.c_name_require",
        "c_name.max" => "VActivityCoupon.c_name_max",
        "desc.max" => "VActivityCoupon.desc_max",
        "slogan.max" => "VActivityCoupon.slogan_max",
        "start_date.require" => "VActivityCoupon.start_date_require",
        "end_date.require" => "VActivityCoupon.end_date_require",
        "c_type.require" => "VActivityCoupon.c_type_require",
        "designated_machine.require" => "VActivityCoupon.designated_machine_require",
        "designated_goods.require" => "VActivityCoupon.designated_goods_require",
        "url_day_count.integer" => "VActivityCoupon.url_day_count_integer",
        "url_day_count.egt" => "VActivityCoupon.url_day_count_egt",
        "url_coupon_count.integer" => "VActivityCoupon.url_coupon_count_integer",
        "url_coupon_count.gt" => "VActivityCoupon.url_coupon_count_gt",
        "need_oauth.in" => "VActivityCoupon.need_oauth_in",
    ];

    protected $scene = [
        "add" => ["c_name","desc","slogan","start_date","end_date","c_type","designated_machine","designated_goods","url_day_count","url_coupon_count","need_oauth"],
        "update" => ["c_id","slogan",'designated_machine','designated_goods',"url_day_count","url_coupon_count","need_oauth"],
        "del" => ["c_id"],
        "takeDown" => ["c_id"],
        "getUrl" => ["c_id"],
    ];
}
