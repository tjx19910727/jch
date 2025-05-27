<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:26
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineConfigLang extends VCommon
{

    protected $rule = [
        "mcl_id" => "require",
        "lang" => "require",
        "mc_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "mcList" => "require",
    ];

    protected $message = [
        "mcl_id.require" => "VMachineConfigLang.mcl_id_require",
        "lang.require" => "VMachineConfigLang.lang_require",
        "mc_id.require" => "VMachineConfigLang.mc_id_require",
        "m_id.require" => "VMachineConfigLang.m_id_require",
        "m_id.unique" => "VMachineConfigLang.m_id_unique",
        "machine_id.require" => "VMachineConfigLang.machine_id_require",
        "mcList.require" => "VMachineConfigLang.mcList_require",
    ];

    protected $scene = [
        "add" => ["mc_id","m_id", "machine_id","lang"],
        "getList" => ["mc_id", "lang"],
        "update" => ["mcl_id"],
        "del" => ["mcl_id"],
        "updateMoreMcl" => ["mcList"],
        "mcList" => ["m_id","lang"],
    ];
}