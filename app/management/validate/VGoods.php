<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 16:01
 */

namespace app\management\validate;


class VGoods extends VCommon
{
    protected $rule = [
        "g_id" => "require",
        "g_name" => "require|max:100",
        "pic" => "max:100",
        "manufacturer" => "max:100",
        "service_phone" => "max:100",
    ];

    protected $message = [
        "g_id.require" => "VMachineGoods.g_id_require",
        "g_name.require" => "VMachineGoods.g_name_require",
        "g_name.max" => "VMachineGoods.g_name_max",
        "pic.max" => "VMachineGoods.pic_max",
        "manufacturer.max" => "VMachineGoods.manufacturer_max",
        "service_phone.max" => "VMachineGoods.service_phone_max",
    ];

    protected $scene = [
        "add" => ["g_name","pic","manufacturer", "service_phone"],
        "del" => ['g_id'],
    ];

    public function sceneUpdate()
    {
        return self::only(["g_id","g_name","pic","manufacturer", "service_phone"])
            ->remove("g_name","require");
    }
}