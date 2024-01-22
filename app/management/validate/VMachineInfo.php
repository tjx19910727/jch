<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate;


class VMachineInfo extends VCommon
{
    protected $rule = [
        "mi_id" => "require",
        "m_id" => "require|unique:machine_info",
        "machine_id" => "require",
    ];

    protected $message = [
        "mi_id.require" => "VMachineInfo.mc_id_require",
        "m_id.require" => "VMachineInfo.m_id_require",
        "m_id.unique" => "VMachineInfo.m_id_unique",
        "machine_id.require" => "VMachineInfo.machine_id_require",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id"],
        "update" => ["mi_id"],
        "del" => ["mi_id"],
    ];
}