<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineChannel extends VCommon
{
    protected $rule = [
        "mc_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "channel_code" => "require",
    ];

    protected $message = [
        "mc_id.require" => "VMachineChannel.mc_id_require",
        "m_id.require" => "VMachineChannel.m_id_require",
        "machine_id.require" => "VMachineChannel.machine_id_require",
        "channel_code.require" => "VMachineChannel.channel_code_require",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id", "channel_code"],
        "update" => ["mc_id"],
        "del" => ["mc_id"],
    ];
}