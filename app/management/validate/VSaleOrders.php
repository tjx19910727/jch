<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/7
 * Time: 10:03
 */

namespace app\management\validate;


class VSaleOrders extends VCommon
{
    protected $rule = [
        "order_id" => "require",
        "details" => "require",
        "mobile" => "require|mobile",

        "ss_id" => "require",
        "quantity" => "require",

        "user_id" => "require",
        "start_time" => "require",
        "end_time" => "require",
        "old_order_id" => "require",
        "store_id" => "require",
        "store_name" => "require",
        "video_url" => "require",

        "refund" => "require",
    ];

    protected $message = [
        "order_id.require" => "订单ID不能为空",
        "details.require" => "商品信息不能为空",

        "mobile.require" => "手机号码不能为空",
        "mobile.mobile" => "手机号码格式错误",

        "ss_id.require" => "货架ID不能为空",
        "quantity.require" => "商品数量不能为空",

        "user_id.require" => "会员ID不能为空",
        "start_time.require" => "开始时间点不能为空",
        "end_time.require" => "结束时间点不能为空",
        "old_order_id.require" => "旧订单ID不能为空",
        "store_id.require" => "门店ID不能为空",
        "store_name.require" => "门店名称不能为空",
        "video_url.require" => "监控视频地址不能为空",

        "refund" => "退款数据不能为空",
    ];

    protected $scene = [
        "supplementary" => ["order_id",'details','mobile'],
        "addDetails" => ["ss_id",'quantity'],

        "saveVideo" => ["order_id","user_id","start_time","end_time","old_order_id","store_id","store_name","video_url"],
        "refund" => ["order_id","refund"],
        "refundDetails" => ["sod_id","quantity"],
    ];
}