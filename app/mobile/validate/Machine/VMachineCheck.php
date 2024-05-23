<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 14:54
 */

namespace app\mobile\validate\Machine;


use app\mobile\validate\VCommon;

class VMachineCheck extends VCommon
{
    protected $rule = [
        "m_id" => "require",
        "type" => "require|in:1,2",
        "checkList" => "require",
        "mc_id" => "require",
        "mg_id" => "require",
        "check_stock" => "require",
        "status" => "require",
    ];
    protected $message = [
        "m_id.require" => "MachineCheck.m_id_require",
        "type.require" => "MachineCheck.type_require",
        "type.in" => "MachineCheck.type_in",
        "checkList.require" => "MachineCheck.checkList_require",
        "mc_id.require" => "MachineCheck.mc_id_require",
        "mg_id.require" => "MachineCheck.mg_id_require",
        "check_stock.require" => "MachineCheck.check_stock_require",
        "status.require" => "MachineCheck.status_require",
    ];
    protected $scene = [
        "stock" => ["m_id","type","checkList"],
        "checkList1" => ["mc_id","check_stock","status"],
        "checkList2" => ["mg_id","check_stock","status"],
    ];
}