<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/1
 * Time: 14:29
 */

namespace app\management\validate;


class VGoodsCorner extends VCommon
{
    protected $rule = [
        "id" => "require",
        "corner_name" => "require",
        "corner_type" => "require",
        "style" => "require",
        "position" => "require",
        "start_time" => "require",
        "goodsList" => "require",
        "machineList" => "require",
    ];
    protected $message = [
        "id.require" => "VGoodsCorner.id_require",
        "corner_name.require" => "VGoodsCorner.corner_name_require",
        "corner_type.require" => "VGoodsCorner.corner_type_require",
        "style.require" => "VGoodsCorner.style_require",
        "position.require" => "VGoodsCorner.position_require",
        "start_time.require" => "VGoodsCorner.start_time_require",
        "goodsList.require" => "VGoodsCorner.goodsList_require",
        "machineList.require" => "VGoodsCorner.machineList_require",
    ];

    protected $scene = [
        "add" => ["corner_name","corner_type","style","position","start_time","goodsList","machineList"],
        "update" => ['id'],
        "del" => ['id'],
    ];
}