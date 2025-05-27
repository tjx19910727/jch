<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:51
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineGroup extends VCommon
{
    protected $rule = [
        "mg_id" => "require",
        "mg_name" => "require|max:100",
        "desc" => "require|max:1024",
    ];

    protected $message = [
        "mg_id.require" => "VMachineGroup.mg_id_require",
        "mg_name.require" => "VMachineGroup.mg_name_require",
        "mg_name.max" => "VMachineGroup.mg_name_max",
        "desc.require" => "VMachineGroup.desc_require",
        "desc.max" => "VMachineGroup.desc_max",
    ];

    protected $scene = [
        "add" => ["mg_name","desc"],
        "update" => ["mg_id","mg_name","desc"],
        "del" => ["mg_id"],
    ];
}