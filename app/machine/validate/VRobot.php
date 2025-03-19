<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/3/18
 * Time: 15:28
 */

namespace app\machine\validate;


class VRobot extends VCommon
{
    protected $rule = [
        "machine_id" => "require",
        "msg_id" => "require|unique:machine_mq_record",
        "timestamp" => "require|checkTimestamp",
        "sign" => "require",
        "position" => "require",
    ];
    protected $message = [
        "machine_id.require" => "VReceive.machine_id_require",
        "msg_id.require" => "VReceive.msg_id_require",
        "msg_id.unique" => "VReceive.msg_id_unique",
        "timestamp.require" => "VReceive.timestamp_require",
        "sign.require" => "VReceive.sign_require",
        "position.require" => "VRobot.position_require",
    ];
    protected $scene = [
        "position" => ["msg_id","machine_id","timestamp","sign","position"],
    ];


    public function checkTimestamp($item)
    {
        if (!$item) return "时间戳不能为空";
        if (time() - $item > 120) return "VReceive.timestamp_checkTimestamp_overdue";
        return true;
    }
}