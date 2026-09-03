<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 19:19
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineGoods extends VCommon
{

    protected $rule = [
        "mg_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "g_id" => "require",
        "g_name" => "require",

    ];

    protected $message = [
        "mg_id.require" => "VMachineGoods.mg_id_require",
        "m_id.require" => "VMachineGoods.m_id_require",
        "machine_id.require" => "VMachineGoods.machine_id_require",
        "g_id.require" => "VMachineGoods.g_id_require",
        "g_name.require" => "VMachineGoods.g_name_require",
    ];

    protected $scene = [
        "add" => ["m_id","machine_id",'g_id',"g_name"],
        "update" => ["mg_id"],
        "updateMore" => ["mg_id"],
        "del" => ["mg_id"],
    ];
}
