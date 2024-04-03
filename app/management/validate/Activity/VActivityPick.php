<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:41
 */

namespace app\management\validate\Activity;


use app\management\validate\VCommon;

class VActivityPick extends VCommon
{
    protected $rule = [
        "id" => "require",
        "pick_name" => "require",
        "start_time" => "require",
        "pick_type" => "require",
        "machineList" => "require",
        "goodsList" => "require",
    ];

    protected $message = [
        "id.require" => "VActivityPick.id_require",
        "pick_name.require" => "VActivityPick.pick_name_require",
        "start_time.require" => "VActivityPick.start_time_require",
        "pick_type.require" => "VActivityPick.pick_type_require",
        "machineList.require" => "VActivityPick.machineList_require",
        "goodsList.require" => "VActivityPick.goodsList_require",
    ];

    protected $scene = [
        "add" => ["pick_name","start_time","pick_type","machineList","goodsList"],
        "update" => ["id"],
        "del" => ["id"],
        "takeDown" => ["id"],
    ];
}