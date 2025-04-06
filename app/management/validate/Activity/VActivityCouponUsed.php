<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 14:30
 */

namespace app\management\validate\Activity;


use app\management\validate\VCommon;

class VActivityCouponUsed extends VCommon
{
    protected $rule = [
        "cu_id" => "require",
        "c_id" => "require",
        "quantity" => "require",
    ];

    protected $message = [
        "cu_id.require" => "VActivityCouponUsed.cu_id_require",
        "c_id.require" => "VActivityCouponUsed.c_id_require",
        "quantity.require" => "VActivityCouponUsed.quantity_require",
    ];

    protected $scene = [
        "getList" => ["c_id"],
        "add" => ["c_id","quantity"],
        "update" => ["cu_id"],
        "del" => ["cu_id"],
    ];
}