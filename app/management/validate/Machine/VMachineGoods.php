<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 19:19
 */

namespace app\management\validate\Machine;

use app\AppFactory\Kernel\Support\SubCarMixPolicy;

use app\management\validate\VCommon;

class VMachineGoods extends VCommon
{

    protected $rule = [
        "mg_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "g_id" => "require",
        "g_name" => "require",
        "sp_id" => "integer|egt:0",
        "sp_ids" => "checkPayeeIds",
        "mg_ids" => "require|checkPayeeIds",

    ];

    protected $message = [
        "mg_id.require" => "VMachineGoods.mg_id_require",
        "m_id.require" => "VMachineGoods.m_id_require",
        "machine_id.require" => "VMachineGoods.machine_id_require",
        "g_id.require" => "VMachineGoods.g_id_require",
        "g_name.require" => "VMachineGoods.g_name_require",
        "sp_id.integer" => "收款策略ID格式错误",
        "sp_id.egt" => "收款策略ID不能小于0",
        "sp_ids.checkPayeeIds" => "收款策略ID列表格式错误",
        "mg_ids.require" => "设备商品ID列表不能为空",
        "mg_ids.checkPayeeIds" => "设备商品ID列表格式错误",
    ];

    protected $scene = [
        "add" => ["m_id","machine_id",'g_id',"g_name","sp_id","sp_ids"],
        "update" => ["mg_id","sp_id","sp_ids"],
        "updatePayeeStrategiesBatch" => ["mg_ids","sp_ids"],
        "updateMore" => ["mg_id"],
        "del" => ["mg_id"],
    ];

    public function checkPayeeIds($value)
    {
        return SubCarMixPolicy::validatePayeeIds($value);
    }
}
