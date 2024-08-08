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
        "pay_method_require" => "支付方式不能为空",
        "total_price_require" => "抽奖金额不能为空",
        "total_quantity_require" => "抽奖数量不能为空",
        "alc_id_require" => "抽奖活动配置ID不能为空",

        "carList_require" => "购物车不能为空",
        "hotelList_require" => "酒店信息不能为空",

        "mcList_require" => "货道列表不能为空",

        "operator_require" => "操作员不能为空",
        "repList_require" => "补货列表不能为空",

        "mc_id_require" => "货道ID不能为空",
        "g_id_require" => "商品ID不能为空",
        "quantity_require" => "库存数量不能为空",
        "standby_quantity_require" => "备用库存数量不能为空",

        "manager_id_require" => "操作员ID不能为空",

        "order_id_require" => "订单ID不能为空",
        "fd_id_require" => "满减满送活动ID不能为空",
        "pick_code_require" => "提货码不能为空",
        "coupon_code_require" => "优惠码不能为空",

        "details_require" => "订单详情不能为空",

        "mvp_id_require" => "版本更新记录ID不能为空",
        "download_progress_require" => "下载进度不能为空",
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
        "mg_no_data" => "查无设备商品库商品信息，可能该设备商品库商品已被删除，请更换该货道商品后再操作补货。",

        "difference_g_id" => "不是原货道商品信息，不允许补货操作",

        "mc_id_require" => "货道ID不能为空",
        "quantity_require" => "补货数量不能为空",

        "exceed_capacity_limit" => "超出货道容量限制",

        "exceed_standby_stock_limit" => "备用库存不足，不允许补货",
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
        "machine_goods_exits" => "该商品已存在于设备中，请勿重复添加",
    ],

    "QueryMachineInfo" => [
        "get_machine_no_data" => "查无设备主体信息",
    ],

    "VSubCar" => [
        "pay_type_no_range" => "系统暂不支持非京东收银支付方式",
        "channel_no_data" => "查无货道信息",
        "make_order_details_fail" => "生成订单详情失败",
        "make_order_success" => "生成订单成功",
        "make_order_fail" => "生成订单失败",

        "goods_outing" => "订单创建成功，正在出货中……",

        "mg_id_require" => "货道未绑定设备商品信息",
        "under_stock" => "货道库存不足",
    ],

    "VOutGoods" => [
        "details_no_data" => "查无出货数据",
    ],

    "VChangeChannelGoods" => [
        "goods_no_data" => "查无商品信息",
        "mg_no_data" => "查无设备商品信息",

        "mg_stock_out" => "设备商品可用库存不足",
    ],

    "VActivityCoupon" => [

        "used_limit" => "此优惠券无剩余使用次数",
        "pay_limit" => "订单金额未达到此优惠券最低消费金额限制",
        "used_coupon" => "该订单已使用优惠券",
        "exclusion" => "当前订单已使用其他优惠活动，不允许使用优惠码",

        "status2" => "此优惠券码已使用",
        "status3" => "此优惠券码已过期",
        "status4" => "此优惠券码已作废",

        "ac_not_data" => "查无优惠券活动",
        "check_no_code" => "查无优惠券码",

        "no_am_data" => "当前设备不能使用此优惠券",
        "no_ag_data" => "当前设备没有指定商品",

        "not_begin" => "活动尚未开始",
        "finished" => "活动已结束",
    ],

    "VActivityPick" => [

        "ap_not_data" => "查无取货码活动",
        "ag_not_data" => "查无取货码活动商品信息",
        "check_no_code" => "查无取货码",

        "not_begin" => "活动尚未开始",
        "finished" => "活动已结束",
    ],

    "VActivityPickCode" => [

        "apc_no_data" => "查无取货码记录",
        "order_no_data" => "查无订单数据",
        "order_out" => "查无订单数据",
        "add_order_fail" => "创建订单信息失败",
        "out_status1" => "该订单已处理，请勿重复提交",

        "status2" => "此取货码已使用",
        "status3" => "此取货码已过期",
        "status4" => "此取货码已作废",
        "status5" => "此取货码正在使用中",

        "mc_id_empty" => "查无可随机取货的货架信息",
    ],

    "VActivityLottery" => [
        "al_no_data" => "查无活动信息",
        "al_not_begin" => "活动还未开始",
        "alc_no_data" => "查无活动配置信息",
        "content_no_data" => "查无活动商品信息",
        "order_no_data" => "查无活动订单信息",
        "order_details_no_data" => "查无订单详细信息",
        "order_type_error" => "订单不是付费抽奖活动订单",
        "order_no_pay" => "订单尚未支付或支付状态异常",
        "mc_no_data" => "查无货道信息",
        "under_stock" => "库存不足",
        "lucky_draw_ended" => "该订单已完成抽奖",

        "used_no_data" => "查无活动订单记录",

        "status3" => "活动已结束",
        "status4" => "活动已下架",


        "lottery_empty" => "无中奖商品，设置错误",

        "probability_no_100" => "中奖概率总和不是100%，无法启用该活动",

        "is_out_goods" => "订单已执行出货",
    ],

    "VActivityFd" => [
        "order_no_data" => "查无订单信息",
        "fd_no_data" => "查无活动信息",
        "content_no_data" => "查无活动内容信息",
        "fd_used" => "已使用该满减活动",
        "no_am_data" => "当前设备不能使用此活动",
        "no_ag_data" => "当前设备没有指定活动商品",

        "exclusion" => "当前订单已使用其他优惠活动，不允许使用满减满赠活动",

    ],

    "VSaleOrders" => [
        "order_no_data" => "查无订单信息",
        "sod_no_data" => "查无订单详情信息",
        "detailsList_no_data" => "未取事件数据不能为空",
    ],

    "VHotel" => [
        "machine_id_require" => "设备编号不能为空",
        "msg_id_require" => "消息ID不能为空",
        "msg_id_unique" => "消息ID已存在，请重新上报",
        "timestamp_require" => "时间戳不能为空",
        "sign_require" => "签名不能为空",
        "timestamp_checkTimestamp_overdue" => "时间戳超时，请更新时间",

        "pageNum_require" => "页面条数不能为空",
        "page_require" => "页码不能为空",
        "cityId_require" => "城市ID不能为空",
        "hotelId_require" => "酒店ID不能为空",
        "checkInDate_require" => "入住日期不能为空",
        "checkOutDate_require" => "离店日期不能为空",
    ],
];