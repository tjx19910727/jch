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
    ];

    protected $message = [
        "m_id.require" => "VMachine.m_id_require",
        "main_m_id.require" => "VSubMachine.main_machine_id_require",
    ];

    protected $scene = [
        "add" => ["main_m_id"],
        "update" => ["m_id,main_m_id"],
        "del" => ["m_id"],
    ];
}
