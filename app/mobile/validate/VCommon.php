<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 14:54
 */

namespace app\mobile\validate;


use think\Validate;

class VCommon extends Validate
{
    protected $rule = [
        "timestamp" => "require|checkTime",
        "sign" => "require",
        "machine_id" => "require",
        "manager_id" => "require",
    ];

    protected $message = [
        "timestamp.require" => "时间戳不能为空",
        "sign.require" => "签名不能为空",
        "manager_id.require" => "操作员ID不能为空",
        "machine_id.require" => "设备ID不能为空",
    ];

    protected $scene = [
        "checkScan" => ['timestamp',"sign","manager_id","machine_id"],
    ];

    public function checkTime($item)
    {
//        if ($item + 180 < time()){
//            return "当前操作已无效";
//        }
        return true;
    }
}