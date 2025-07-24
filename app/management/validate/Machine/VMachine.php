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
    ];

    protected $message = [
        "m_id.require" => "VMachine.m_id_require",
        "machine_id.require" => "VMachine.machine_id_require",
        "machine_id.alphaDash" => "VMachine.machine_id_alphaDash",
        "machine_id.unique" => "VMachine.machine_id_exists",
        "status.in" => "VMachine.status_in",
    ];

    protected $scene = [
        "add" => ["machine_id","status"],
        "update" => ["m_id","status"],
        "updateMore" => ["m_id"],
        "del" => ["m_id"],
    ];
}