<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 11:54
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineVersionPlan extends VCommon
{
    protected $rule = [
        "mvp_id" => "require",
        "mv_id" => "require",
        "m_id" => "require",
        "publish_time" => "require",
    ];

    protected $message = [
        "mvp_id.require" => "请选择更新计划",
        "mv_id.require" => "请选择设备软件",
        "m_id.require" => "请选择需要更新的设备",
        "publish_time.require" => "请设置发布时间",
    ];

    protected $scene = [
        "add" => ["mv_id","m_id"],
        "del" => ["mvp_id"],
    ];
}