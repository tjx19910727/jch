<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:41
 */

namespace app\management\validate\Activity;


use app\management\validate\VCommon;

class VActivityPickCode extends VCommon
{
    protected $rule = [
        "apc_id" => "require",
        "id" => "require",
        "ap_id" => "require",
        "quantity" => "require",

        "pick_code" => "require",
        "m_id" => "require",

    ];

    protected $message = [
        "apc_id.require" => "VActivityPickCode.apc_id_require",
        "id.require" => "VActivityPickCode.ap_id_require",
        "ap_id.require" => "VActivityPickCode.ap_id_require",
        "quantity.require" => "VActivityPickCode.quantity_require",
        "pick_code.require" => "VActivityPickCode.pick_code_require",
        "m_id.require" => "VActivityPickCode.m_id_require",
    ];

    protected $scene = [
        "getList" => ["ap_id"],
        "add" => ["id","quantity"],
        "update" => ["apc_id"],
        "del" => ["apc_id"],
        "usePickCode" => ['pick_code','m_id'],
    ];
}