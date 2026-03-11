<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:54
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineLevelDesc extends VCommon
{
    protected $rule = [
        "machine_level" => "require|gt:0",
        "name" => "require",
        "pic" => "require",
    ];

    protected $message = [
        "machine_level.require" => "VMachineLevelDesc.machine_level_require",
        "machine_level.gt" => "VMachineLevelDesc.machine_level_gt",
        "name.require" => "VMachineLevelDesc.name_require",
        "pic.require" => "VMachineLevelDesc.pic_require",
    ];

    protected $scene = [
        "add" => ["name","pic"],
        "update" => ["machine_level","name","pic"],
        "del" => ["machine_level"],
    ];
}