<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate\Machine;


use app\AppFactory\Kernel\Support\SubCarMixPolicy;
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
        "subcar_mix" => "in:1,2",
        "subcar_offline_sp_ids" => "checkPayeeIds",
        "subcar_online_sp_ids" => "checkPayeeIds",
    ];

    protected $message = [
        "mc_id.require" => "VMachineConfig.mc_id_require",
        "m_id.require" => "VMachineConfig.m_id_require",
        "m_id.unique" => "VMachineConfig.m_id_unique",
        "machine_id.require" => "VMachineConfig.machine_id_require",
        "mcList.require" => "VMachineConfig.mcList_require",
        "online_pay_success_tip.max" => "VMachineConfig.online_pay_success_tip_max",
        "run_mode.in" => "VMachineConfig.run_mode_in",
        "subcar_mix.in" => "VMachineConfig.subcar_mix_in",
        "subcar_offline_sp_ids.checkPayeeIds" => "VMachineConfig.subcar_offline_sp_ids_invalid",
        "subcar_online_sp_ids.checkPayeeIds" => "VMachineConfig.subcar_online_sp_ids_invalid",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id", "online_pay_success_tip", "run_mode", "subcar_mix", "subcar_offline_sp_ids", "subcar_online_sp_ids"],
        "update" => ["mc_id", "online_pay_success_tip", "run_mode", "subcar_mix", "subcar_offline_sp_ids", "subcar_online_sp_ids"],
        "del" => ["mc_id"],
        "updateMoreMc" => ["mcList"],
        "mcList" => ["m_id", "online_pay_success_tip", "run_mode", "subcar_mix", "subcar_offline_sp_ids", "subcar_online_sp_ids"],
    ];

    public function sceneMcList()
    {
        return $this->only(['m_id', 'online_pay_success_tip', 'run_mode', 'subcar_mix', 'subcar_offline_sp_ids', 'subcar_online_sp_ids'])
            ->remove("m_id",'unique');
    }

    public function checkPayeeIds($value)
    {
        return SubCarMixPolicy::validatePayeeIds($value);
    }
}
