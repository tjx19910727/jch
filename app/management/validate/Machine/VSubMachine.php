<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:54
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VSubMachine extends VCommon
{
    protected $rule = [
        "m_id" => "require",
        "main_m_id" => "require",
        "machine_id" => "require|alphaDash|unique:machine_auxiliary",
        "machine_type" => "require",
    ];

    protected $message = [
        "m_id.require" => "VMachine.m_id_require",
        "main_m_id.require" => "VSubMachine.main_machine_id_require",
        "machine_id.require" => "VMachine.machine_id_require",
        "machine_id.alphaDash" => "VMachine.machine_id_alphaDash",
        "machine_id.unique" => "VMachine.machine_id_exists",
        "machine_type.require" => "VSubMachine.machine_type_require",
    ];

    protected $scene = [
        "add" => ["main_m_id","machine_id","machine_type"],
        "update" => ["m_id","main_m_id","machine_type"],
        "del" => ["m_id"],
    ];
}
