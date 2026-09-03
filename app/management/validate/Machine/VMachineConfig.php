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
        "is_multi_goods" => "in:1,2",
        "add_other_org_goods" => "in:1,2",
        "subcar_mix" => "in:1,2",
        "goods_no_stock_jump_to_mini_program" => "in:1,2",
        "subcar_offline_sp_ids" => "checkPayeeIds",
        "subcar_online_sp_ids" => "checkPayeeIds",
        "currency_code" => "require|regex:^[A-Za-z]{3}$",
        "m_ids" => "require",
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
        "add_other_org_goods.in" => "VMachineConfig.add_other_org_goods_in",
        "subcar_mix.in" => "VMachineConfig.subcar_mix_in",
        "goods_no_stock_jump_to_mini_program.in" => "VMachineConfig.goods_no_stock_jump_to_mini_program_in",
        "subcar_offline_sp_ids.checkPayeeIds" => "VMachineConfig.subcar_offline_sp_ids_invalid",
        "subcar_online_sp_ids.checkPayeeIds" => "VMachineConfig.subcar_online_sp_ids_invalid",
        "currency_code.require" => "币种代码不能为空",
        "m_ids.require" => "请选择设备",
    ];

    protected $scene = [
        "add" => ["m_id", "machine_id", "online_pay_success_tip", "add_other_org_goods", "run_mode", "subcar_mix", "goods_no_stock_jump_to_mini_program", "subcar_offline_sp_ids", "subcar_online_sp_ids"],
        "update" => ["mc_id", "online_pay_success_tip", "add_other_org_goods", "run_mode", "is_multi_goods", "subcar_mix", "goods_no_stock_jump_to_mini_program", "subcar_offline_sp_ids", "subcar_online_sp_ids"],
        "del" => ["mc_id"],
        "updateMoreMc" => ["mcList"],
        "mcList" => ["m_id", "online_pay_success_tip", "add_other_org_goods", "run_mode", "is_multi_goods", "subcar_mix", "goods_no_stock_jump_to_mini_program", "subcar_offline_sp_ids", "subcar_online_sp_ids"],

        "currencySwitchBatch" => ["m_ids", "currency_code"],
    ];

    public function sceneCurrencySwitch()
    {
        return $this->only(['m_id', 'currency_code'])->remove('m_id', 'unique');
    }
    public function sceneMcList()
    {
        return $this->only(['m_id', 'online_pay_success_tip', 'add_other_org_goods', 'run_mode', 'is_multi_goods', 'subcar_mix', 'goods_no_stock_jump_to_mini_program', 'subcar_offline_sp_ids', 'subcar_online_sp_ids'])
            ->remove("m_id",'unique');
    }

    public function checkPayeeIds($value)
    {
        return SubCarMixPolicy::validatePayeeIds($value);
    }
}
