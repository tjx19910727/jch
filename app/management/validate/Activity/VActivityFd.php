<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 11:47
 */

namespace app\management\validate\Activity;


use app\management\validate\VCommon;

class VActivityFd extends VCommon
{
    protected $rule = [
        "fd_id" => "require",
        "fd_name" => "require",
        "start_date" => "require",
        "fd_type" => "require",
        "condition_type" => "require",
        "machineList" => "require",
        "content" => "require",

        // "condition_value" => "require",
        "active_value" => "require",
        "sort" => "require",
    ];

    protected $message = [
        "fd_id.require" => "VActivityFd.fd_id_require",
        "fd_name.require" => "VActivityFd.fd_name_require",
        "start_date.require" => "VActivityFd.start_date_require",
        "fd_type.require" => "VActivityFd.fd_type_require",
        "condition_type.require" => "VActivityFd.condition_type_require",
        "machineList.require" => "VActivityFd.machineList_require",
        "content.require" => "VActivityFd.content_require",

        // "condition_value.require" => "VActivityFd.condition_value_require",
        "active_value.require" => "VActivityFd.active_value_require",
        "sort.require" => "VActivityFd.sort_require",
    ];

    protected $scene = [
        "add" => ["fd_name","start_date","fd_type","condition_type","machineList","content"],
        "addContent" => [/** "condition_value",*/"active_value","sort"],
        "getFind" => ["fd_id"], 
        "update" => ["fd_id","content"],
        "del" =>["fd_id"],
        "takeDown" =>["fd_id"],
    ];
}