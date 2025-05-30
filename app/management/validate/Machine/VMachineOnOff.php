<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/8
 * Time: 16:55
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineOnOff extends VCommon
{

        protected $rule = [
            "moo_id" => "require",
            "m_id" => "require",
            "machine_id" => "require",
            "machine_name" => "require",
            "on_off_ckc" => "require",
            "on_off_machine" => "require",
        ];

        protected $message = [
            "moo_id.require" => "VMachineOnOff.moo_id_require",
            "m_id.require" => "VMachineOnOff.m_id_require",
            "machine_id.require" => "VMachineOnOff.machine_id_require",
            "on_off_ckc.require" => "VMachineOnOff.on_off_ckc_require",
            "on_off_machine.require" => "VMachineOnOff.on_off_machine_require",
        ];

        protected $scene = [
            "add" => ["m_id"],
            "update" => ["moo_id"],
            "del" => ["moo_id"],
        ];
}