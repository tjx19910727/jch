<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 14:44
 */

namespace app\management\validate;


class VStrategyMachine extends VCommon
{
    protected $rule = [
        "ss_id" => "require",
        "s_id" => "require",
        "store_id" => "require",
        "s_type" => "require",
    ];

    protected $message = [
        "ss_id" => "绑定ID不能为空",
        "s_id" => "策略ID不能为空",
        "m_id" => "设备ID不能为空",
        "s_type" => "策略类型不能为空",
    ];

    protected $scene = [
        "bind" => ['s_id',"m_id",'s_type'],
        "unbind" => ['ss_id'],
    ];
}