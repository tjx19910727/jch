<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 11:28
 */

namespace app\management\validate\Goods;


use app\management\validate\VCommon;

class VGoodsMultiple extends VCommon
{

        protected $rule = [
            "gm_id" => "require",
            "gm_name" => "require",
            "start_time" => "require",
            "mList" => "require",
            "m_id" => "require",
            "machine_id" => "require",
            "gList" => "require",
            "g_id" => "require",
            "selling_price" => "require",
            "rise_fall_ratio" => "require",
        ];

        protected $message = [
            "gm_id.require" => "VGoodsMultiple.gm_id_require",
            "gm_name.require" => "VGoodsMultiple.gm_name_require",
            "start_time.require" => "VGoodsMultiple.start_time_require",
            "mList.require" => "VGoodsMultiple.mList_require",
            "m_id.require" => "VGoodsMultiple.m_id_require",
            "machine_id.require" => "VGoodsMultiple.machine_id_require",
            "gList.require" => "VGoodsMultiple.gList_require",
            "g_id.require" => "VGoodsMultiple.g_id_require",
            "selling_price.require" => "VGoodsMultiple.selling_price_require",
            "rise_fall_ratio.require" => "VGoodsMultiple.rise_fall_ratio_require",
        ];

        protected $scene = [
            "add" => ["gm_name",'start_time',"mList","gList"],
            "mList" => ["m_id","machine_id"],
            "gList" => ["gm_id","g_id","selling_price","rise_fall_ratio"],
            "update" => ["gm_id"],
            "del" => ["gm_id"],
        ];
}