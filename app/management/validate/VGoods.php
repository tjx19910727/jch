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
        "g_name" => "require|length:0,100",
        "sku" => "require",
        "banner" => "length:0,1024",
        "pic" => "length:0,100",
        "manufacturer" => "length:0,100",
        "service_phone" => "length:0,100",
        "release_time" => "require",
    ];

    protected $message = [
        "g_id.require" => "VGoods.g_id_require",
        "g_name.require" => "VGoods.g_name_require",
        "g_name.length" => "VGoods.g_name_max",
        "sku.require" => "VGoods.sku_require",
        "pic.length" => "VGoods.pic_max",
        "banner.length" => "VGoods.banner_max",
        "manufacturer.length" => "VGoods.manufacturer_max",
        "service_phone.length" => "VGoods.service_phone_max",
        "release_time.require" => "VGoods.release_time_require",
    ];

    protected $scene = [
        "add" => ["g_name","sku","banner","pic","manufacturer", "service_phone","release_time"],
        "del" => ['g_id'],
    ];

    public function sceneUpdate()
    {
        return self::only(["g_id","g_name","pic","manufacturer", "service_phone"])
            ->remove("g_name","require");
    }
}