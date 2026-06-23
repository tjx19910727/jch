<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VOtaVersionPlan extends VCommon
{
    protected $rule = [
        "ovp_id" => "require",
        "ov_id" => "require",
        "m_id" => "require",
        "publish_time" => "require",
    ];

    protected $message = [
        "ovp_id.require" => "请选择OTA更新计划",
        "ov_id.require" => "请选择OTA固件",
        "m_id.require" => "请选择需要更新的设备",
        "publish_time.require" => "请设置发布时间",
    ];

    protected $scene = [
        "add" => ["ov_id", "m_id"],
        "del" => ["ovp_id"],
    ];
}
