<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 19:19
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineGoods extends VCommon
{

    protected $rule = [
        "mg_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "g_id" => "require",
        "g_name" => "require",
        "start_time" => "require",
        "end_time" => "require",
    ];

    protected $message = [
        "mg_id.require" => "VMachineGoods.mg_id_require",
        "m_id.require" => "VMachineGoods.m_id_require",
        "machine_id.require" => "VMachineGoods.machine_id_require",
        "g_id.require" => "VMachineGoods.g_id_require",
        "g_name.require" => "VMachineGoods.g_name_require",
        "start_time.require" => "请选择开始时间",
        "end_time.require" => "请选择结束时间",
    ];

    protected $scene = [
        "add" => ["m_id","machine_id",'g_id',"g_name"],
        "update" => ["mg_id"],
        "updateMore" => ["mg_id"],
        "del" => ["mg_id"],
        // 库存变化统计：主统计与三类明细共用同一组查询条件（m_id + g_id + 起止时间）
        "stockChangeStats" => ["m_id", "g_id", "start_time", "end_time"],
        "stockChangeDetail" => ["m_id", "g_id", "start_time", "end_time"],
    ];
}
