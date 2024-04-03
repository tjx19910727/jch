<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 18:01
 */

namespace app\management\validate\Activity;


use app\management\validate\VCommon;

class VActivityLottery extends VCommon
{
    protected $rule = [
        "al_id" => "require",
        "lottery_name" => "require",
        "start_time" => "require",
        "price" => "require",
        "desc" => "max:255",

        "config" => "require",
        "content" => "require",
        "machineList" => "require",
        "delContent" => "require",
        "delConfig" => "require",

        "content_name" => "require",
        "designated_goods" => "require",
        "retain_num" => "require",
        "probability" => "require",

        "g_id" => "require",
    ];
    protected $message = [
        "al_id.require" => "VActivityLottery.al_id_require",
        "lottery_name.require" => "VActivityLottery.lottery_name_require",
        "start_time.require" => "VActivityLottery.start_time_require",
        "price.require" => "VActivityLottery.price_require",
        "desc.max" => "VActivityLottery.desc_max",

        "config.require" => "VActivityLottery.config_require",
        "content.require" => "VActivityLottery.content_require",
        "machineList.require" => "VActivityLottery.machineList_require",
        "delContent.require" => "VActivityLottery.delContent_require",
        "delConfig.require" => "VActivityLottery.delConfig_require",

        "content_name.require" => "VActivityLottery.content_name_require",
        "designated_goods.require" => "VActivityLottery.designated_goods_require",
        "retain_num.require" => "VActivityLottery.retain_num_require",
        "probability.require" => "VActivityLottery.probability_require",

        "g_id.require" => "VGoods.g_id_require",

        "active_num.require" => "VActivityLottery.active_num_require",
        "active_type.require" => "VActivityLottery.active_type_require",
    ];
    protected $scene = [
        "add" => ["lottery_name","start_time","price", "config","content","machineList"],
        "addContent" => ["content_name",'retain_num','probability',"g_id"],
        "addConfig" => ['active_num','active_type'],
        "machineList" => ["m_id"],
        "update" => ["al_id",'content','machineList'],
        "del" => ["al_id"],
        "takeDown" => ["al_id"],
    ];
}