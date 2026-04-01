<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/31
 * Time: 11:00
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineCalibrationConfig extends VCommon
{
    protected $rule = [
        "id" => "require",
        "m_id" => "requireWithout:machine_id",
        "machine_id" => "requireWithout:m_id",
        "key" => "require",
        "title" => "require",
        "data" => "require|array",
    ];

    protected $message = [
        "id.require" => "id不能为空",
        "m_id.requireWithout" => "m_id和machine_id不能同时为空",
        "machine_id.requireWithout" => "machine_id和m_id不能同时为空",
        "key.require" => "key不能为空",
        "title.require" => "title不能为空",
        "data.require" => "data不能为空",
        "data.array" => "data必须是数组",
    ];

    protected $scene = [
        "getList" => ["m_id", "machine_id"],
        "add" => ["m_id", "machine_id", "key", "title"],
        "updateList" => ["m_id", "machine_id", "data"],
    ];
}
