<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/8
 * Time: 15:27
 */

namespace app\management\validate;


class VActivity extends VCommon
{
    protected $rule = [

        // 活动主体信息
        "store_id" => "require",
        "store_name" => "require",
        "terminal_no" => "require",
        "store_manager" => "require",
        "activity_name" => "require",
        "start_date" => "require",
        "end_date" => "require",
        "status" => "require",

        // 限时折扣
        "ad_id" => "require",
        "discount" => "require",

        // 满减活动
        "full_type" => "require",
        "full" => "require",
        "dec_type" => "require",
        "dec" => "require",

        // 时间列表、商品列表参数
        "timeList" => "require",
        "goodsList" => "require",


        // 商品列表详情
        "ag_id" => "require",
//        "a_type" => "require",
        "a_id" => "require",
//        "store_id" => "int",
        "ss_id" => "require",
        "shelves_number" => "require",
        "wg_id" => "require",
        "goods_id" => "require",
        "goods_name" => "require",
        "goods_pic" => "require",
        "goods_c_id" => "require",
        "goods_c_name" => "require",

        // 时间列表详情
        "at_id" => "require",
//        "a_id" => "int",
//        "a_type" => "int",
        "start_time" => "require",
        "end_time" => "require",

        // 加价换购活动内容商品信息
        "ahg_id" => "require",
        "ah_id" => "require",
        "hgGoodsList" => "require",
        "full_order_amount" => "require",
        "amount" => "require",
        "limit_num" => "require",
    ];

    protected $message = [
        // 活动主体信息
        "store_id.require" => "门店ID不能为空",
        "store_name.require" => "门店名称不能为空",
        "terminal_no.require" => "门店设备终端编号不能为空",
        "store_manager.require" => "门店店长不能为空",
        "activity_name.require" => "活动名称不能为空",
        "start_date.require" => "开始日期不能为空",
        "end_date.require" => "结束日期不能为空",
        "status.require" => "状态不能为空",

        // 时间段列表、商品列表
        "timeList.require" => "时间段列表不能为空",
        "goodsList.require" => "商品列表不能为空",

        // 限时折扣
        "ad_id.require" => "活动ID不能为空",
        "discount.require" => "折扣值不能为空",

        // 满减活动
        "full_type.require" => "满减条件类型不能为空" ,
        "full.require" => "满减条件不能为空" ,
        "dec_type.require" => "满减优惠类型不能为空" ,
        "dec.require" => "满减优惠内容不能为空" ,

        // 商品列表详情
        "ag_id.require" => "活动商品ID不能为空",
        "a_id.require" => "活动ID不能为空",
        "ss_id.require" => "货架ID不能为空",
        "shelves_number.require" => "货架编号不能为空",
        "wg_id.require" => "仓库商品ID不能为空",
        "goods_id.require" => "商品ID不能为空",
        "goods_name.require" => "商品名称不能为空",
        "goods_pic.require" => "商品图片不能为空",
        "goods_c_id.require" => "商品分类ID不能为空",
        "goods_c_name.require" => "商品分类名称不能为空",

        // 时间段列表详情
        "at_id.require" => "活动时间关联ID不能为空",
        "start_time.require" => "开始时间段不能为空",
        "end_time.require" => "结束时间段不能为空",

        // 加价换购活动内容商品信息
        "ahg_id.require" => "活动内容商品ID不能为空",
        "ah_id.require" => "活动ID不能为空",
        "hgGoodsList.require" => "活动内容不能为空",
        "full_order_amount.require" => "订单满金额不能为空",
        "amount.require" => "加价不能为空",
        "limit_num.require" => "换购上限数量不能为空",
    ];

    protected $scene = [
        "addDiscount" => ["store_id","activity_name","start_date","end_date","discount","status","timeList","goodsList"],
        "updateDiscount" => ["ad_id"],

        "addGoods" => ["ss_id"],
        "updateGoods" => ["ag_id"],

        "addTime" => ["start_time","end_time"],
        "updateTime" => ["at_id"],

        "addFullDec" => ["store_id","activity_name","start_date","end_date","full_type","full","dec_type","dec","status","timeList","goodsList"],
        "updateFullDec" => ["afd_id"],

        "addHgG" => ["ah_id","hgGoodsList"],
        "addHgGoods" => ["ah_id","full_order_amount","wg_id","goods_id","amount","limit_num"],
        "updateHgGoods" => ["ahg_id"],
    ];
}