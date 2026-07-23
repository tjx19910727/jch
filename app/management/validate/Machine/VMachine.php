<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:54
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachine extends VCommon
{
    protected $rule = [
        "m_id" => "require",
        "machine_id" => "require|alphaDash|unique:machine",
        "status" => "in:1,2,3",
        "is_operating" => "in:1,2,3",
        "run_mode" => "in:1,2",
    ];

    protected $message = [
        "m_id.require" => "VMachine.m_id_require",
        "machine_id.require" => "VMachine.machine_id_require",
        "machine_id.alphaDash" => "VMachine.machine_id_alphaDash",
        "machine_id.unique" => "VMachine.machine_id_exists",
        "status.in" => "VMachine.status_in",
        "is_operating.in" => "VMachine.is_operating_in",
        "run_mode.in" => "VMachine.run_mode_in",
    ];

    protected $scene = [
        "add" => ["machine_id","status","run_mode"],
        "update" => ["m_id","status","run_mode"],
        "updateMore" => ["m_id"],
        "setOperating" => ["m_id","is_operating"],
        "del" => ["m_id"],
    ];
}
