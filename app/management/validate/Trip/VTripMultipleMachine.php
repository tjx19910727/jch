<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:56
 */

namespace app\management\validate\Trip;


use app\management\validate\VCommon;

class VTripMultipleMachine extends VCommon
{
    protected $rule = [
        "tmm_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "machine_name" => "require",
    ];

    protected $message = [
        "tmm_id.require" => "VTripMultiple.tmm_id_require",
        "m_id.require" => "VTripMultiple.m_id_require",
        "machine_id.require" => "VTripMultiple.machine_id_require",
        "machine_name.require" => "VTripMultiple.machine_name_require",
    ];

    protected $scene = [
        "add" => ["m_id","machine_id","machine_name"],
        "update" => ["tmm_id"],
        "del" => ["tmm_id"],
    ];
}