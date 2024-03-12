<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 11:09
 */

return [
    "check_sign_fail" => "验签失败",
    "query_mv_no_data" => "查无设备视图数据",

    "VLogin" => [
        "not_manager" => "当前设备未设置管理员",

        "account_pwd_error" => "账号或密码错误",
        "account_disabled" => "该账号已被禁用",
        "login_success" => "登录成功",
        "logout_success" => "退出登录成功",
    ],

    "VReceive" => [
        "machine_id_require" => "设备编号不能为空",
        "msg_id_require" => "消息ID不能为空",
        "msg_id_unique" => "消息ID已存在，请重新上报",
        "timestamp_require" => "时间戳不能为空",
        "sign_require" => "签名不能为空",
        "timestamp_checkTimestamp_overdue" => "时间戳超时，请更新时间",

        "pay_type_require" => "支付类型不能为空",
        "carList_require" => "购物车不能为空",

        "mcList_require" => "货道列表不能为空",

        "operator_require" => "操作员不能为空",
        "repList_require" => "补货列表不能为空",

        "mc_id_require" => "货道ID不能为空",
        "g_id_require" => "商品ID不能为空",
        "quantity_require" => "库存数量不能为空",


    ],

    "VChannel" => [
        "channel_code_require" => "货道编号不能为空",
        "channel_position_require" => "货道位置不能为空",

        "add_channel_fail" => "添加货道失败",
        "update_channel_fail" => "修改货道失败",
    ],

    "VChannelReplenishment" => [
        "non-administrators" => "当前操作员非管理人员，不允许执行补货操作",
        "channel_no_data" => "查无货道信息",

        "difference_g_id" => "不是原货道商品信息，不允许补货操作",

        "mc_id_require" => "货道ID不能为空",
        "quantity_require" => "补货数量不能为空",

        "exceed_capacity_limit" => "超出货道容量限制",
    ],

    "VAdvertisement" => [
        "adv_no_data" => "查无广告信息",
        "adv_complete" => "广告已完成",
        "adv_id_require" => "广告ID不能为空",
        "play_time_require" => "播放时间不能为空",
    ],



    "VMachineGoods" => [
        "update_machine_goods_fail" => "修改设备商品失败",
        "goods_no_data" => "查无商品信息",
        "mg_id_require" => "设备商品ID不能为空",
        "g_id_require" => "商品ID不能为空",
    ],

    "QueryMachineInfo" => [
        "get_machine_no_data" => "查无设备主体信息",
    ],

    "VSubCar" => [
        "channel_no_data" => "查无货道信息",
        "make_order_details_fail" => "生成订单详情失败",
        "make_order_success" => "生成订单成功",
        "make_order_fail" => "生成订单失败",
    ],

    "VChangeChannelGoods" => [
        "goods_no_data" => "查无商品信息",
    ],

    "VActivityCoupon" => [

        "used_limit" => "此优惠券无剩余使用次数",
        "pay_limit" => "订单金额未达到此优惠券最低消费金额限制",

        "status2" => "此优惠券码已使用",
        "status3" => "此优惠券码已过期",
        "status4" => "此优惠券码已作废",

        "ac_not_data" => "查无优惠券活动",
        "check_no_code" => "查无优惠券码",

        "not_begin" => "活动尚未开始",
        "finished" => "活动已结束",
    ],
];