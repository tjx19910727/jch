<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 19:32
 */

return [
    "VV2" => [
        "machine_id_require" => "机器编号不能为空",
        "shelf_on_require" => "是否上架不能为空",

        "pageNum_require" => "页面数据条数不能为空",

    ],
    "msg" => [
        0 => "成功",
        1 => "无效IP地址",
        2 => "无效用户",
        3 => "密码错误",
        4 => "不存在API",
        5 => "JSON格式错误",
        6 => "缺少参数",
        7 => "无效参数",
        8 => "访问次数超过每天次数限定",
        9 => "访问过于频繁",
        10 => "找不到需要的数据",
        11 => "连不上机器",
        12 => "许可证不能用",
        13 => "时间跨度大于30天",
        14 => "预订商品ID不正确或没有足够的商品",
        15 => "数据不存在",
        16 => "无效时间戳",
        17 => "签名错误",
        18 => "生成取货码记录失败",
        19 => "操作失败",
        20 => "取货码使用中",
        21 => "该订单已执行操作，请勿重复提交",
        22 => "库存不足",
        23 => "无效订单编号 ",
        24 => "订单处理中",
        25 => "订单支付类型不匹配",
        26 => "查无酒店信息",
        27 => "查无该活动信息",
        28 => "该活动已结束",
        29 => "该活动已下架",
        30 => "该活动尚未开始",
        31 => "该活动已过期",
        32 => "此优惠券无剩余使用次数",
        33 => "该活动无剩余券可领取",
        34 => "生成领取记录失败",
        35 => "修改领取记录失败",
        36 => "查无订单数据",
        37 => "查无订单详情数据",
        38 => "查无商品信息",
        39 => "商品库存不足",
        40 => "查无设备信息",
        41 => "查无取货码信息",
        97 => "请使用multipart/form-data",
        98 => "请使用Post请求方式",
        99 => "服务不可用",
    ],

    "reserve_order" => [
        "machine_no_data" => "查无设备信息",
        "machine_offline" => "设备离线",
        "under_stock" => "库存不足",

        "apc_id_add_fail" => "生成取货码记录失败",
        "apc_already_exist" => "取货码已存在",

        "kiosk_id_require" => "机器编号不能为空",
        "order_no_require" => "订单编号不能为空",
        "payment_method_require" => "支付方式不能为空",
        "expire_time_require" => "订单有效期不能为空",
        "charge_time_require" => "支付时间不能为空",
        "order_detail_require" => "订单详情不能为空",
    ],
    "order_detail" => [
        "quantity_require" => "预定数量不能为空",
        "item_price_require" => "销售价格不能为空",
        "discount_amount_require" => "优惠价格不能为空",
        "charge_amount_require" => "实际支付总价格不能为空",
        "type_require" => "类型不能为空",
    ],

    "payNotify" => [
        "order_no_require" => "订单编号不能为空",
        "pay_status" => "支付结果不能为空",
        "shelf_on_require" => "商品上架状态不能为空",
    ],

    "activity_code" => [
        "aId_require" => "活动ID不能为空",
        "aType_require" => "活动类型不能为空",
    ],

    "use_pick_code" => [
        "pick_code_require" => "取货码不能为空",
        "null_data" => "查无提货码信息",
        "order_null_data" => "查无订单信息",
        "order_details_null_data" => "查无订单详情信息",

        "status2" => "该活动码已被使用",
        "status3" => "该活动码已过期",
        "status4" => "该活动码已作废",
        "status5" => "该活动码使用中，请勿重复提交",

        "trans_fail" => "事务执行失败",
        "device_type_unDefine" => "未定义的应用类型",
    ],
    "robot" => [
        "machine_no_data" => "查无设备信息",
    ],
];