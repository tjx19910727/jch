<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate;


class VMachineHelp extends VCommon
{
    protected $rule = [
        "mh_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
    ];

    protected $message = [
        "mh_id.require" => "VMachineInfo.mc_id_require",
        "m_id.require" => "VMachineInfo.m_id_require",
        "machine_id.require" => "VMachineInfo.machine_id_require",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id"],
        "update" => ["mh_id"],
        "del" => ["mh_id"],
    ];
}