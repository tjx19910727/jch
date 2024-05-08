<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:51
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineGroupMg extends VCommon
{
    protected $rule = [
        "mg_id" => "require",
        "m_id" => "require",
    ];

    protected $message = [
        "mg_id.require" => "VMachineGroupMg.mg_id_require",
        "m_id.require" => "VMachineGroupMg.m_id_require",
    ];

    protected $scene = [
        "bind" => ["m_id","mg_id"],
    ];
}