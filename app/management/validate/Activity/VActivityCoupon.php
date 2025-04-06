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
    ];

    protected $scene = [
        "add" => ["c_name","desc","start_date","end_date","c_type","designated_machine","designated_goods"],
        "update" => ["c_id",'designated_machine','designated_goods'],
        "del" => ["c_id"],
        "takeDown" => ["c_id"],
    ];
}