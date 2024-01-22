<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate;


class VMachineConfig extends VCommon
{
    protected $rule = [
        "mc_id" => "require",
        "m_id" => "require|unique:machine_config",
        "machine_id" => "require",
    ];

    protected $message = [
        "mc_id.require" => "VMachineConfig.mc_id_require",
        "m_id.require" => "VMachineConfig.m_id_require",
        "m_id.unique" => "VMachineConfig.m_id_unique",
        "machine_id.require" => "VMachineConfig.machine_id_require",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id"],
        "update" => ["mc_id"],
        "del" => ["mc_id"],
    ];
}