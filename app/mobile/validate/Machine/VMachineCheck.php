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
        "mc_id" => "require",
        "real_stock" => "require",
        "status" => "require",
    ];
    protected $message = [
        "m_id.require" => "设备ID不能为空",
        "mc_id.require" => "设备货道ID不能为空",
        "real_stock.require" => "货道真实库存不能为空",
        "status.require" => "盘点状态不能为空",
    ];
    protected $scene = [
        "stock" => ["m_id","mc_id","real_stock","status"],
    ];
}