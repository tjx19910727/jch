<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineConfig extends VCommon
{
    protected $rule = [
        "mc_id" => "require",
        "m_id" => "require|unique:machine_config",
        "machine_id" => "require",
        "mcList" => "require",
        "online_pay_success_tip" => "max:255",
        "run_mode" => "in:1,2",
        "is_multi_goods" => "in:1,2",
    ];

    protected $message = [
        "mc_id.require" => "VMachineConfig.mc_id_require",
        "m_id.require" => "VMachineConfig.m_id_require",
        "m_id.unique" => "VMachineConfig.m_id_unique",
        "machine_id.require" => "VMachineConfig.machine_id_require",
        "mcList.require" => "VMachineConfig.mcList_require",
        "online_pay_success_tip.max" => "VMachineConfig.online_pay_success_tip_max",
        "run_mode.in" => "VMachineConfig.run_mode_in",
        "is_multi_goods.in" => "单货道多商品开关参数错误",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id", "online_pay_success_tip", "run_mode"],
        "update" => ["mc_id", "online_pay_success_tip", "run_mode", "is_multi_goods"],
        "del" => ["mc_id"],
        "updateMoreMc" => ["mcList"],
        "mcList" => ["m_id", "online_pay_success_tip", "run_mode", "is_multi_goods"],
    ];

    public function sceneMcList()
    {
        return $this->only(['m_id', 'online_pay_success_tip', 'run_mode', 'is_multi_goods'])->remove("m_id",'unique');
    }
}
