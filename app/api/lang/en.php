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


    ],
    "msg" => [
        0 => "Success",
        1 => "Invalid IP Address",
        2 => "Invalid User",
        3 => "Wrong Password",
        4 => "No Such API",
        5 => "JSON Syntax Error",
        6 => "Missing Fields",
        7 => "Invalid Fields",
        8 => "Requests Count Limited",
        9 => "Requests Interval Limited",
        10 => "Data not Found",
        11 => "Can not Contact the Kiosk",
        12 => "License Expired",
        13 => "Exceed Date Range(30 days)",
        14 => "Product is unavailable",
        15 => "table is nonexistent.",
        16 => "Invalid Timestamp",
        17 => "Sign Error",
        18 => "Failed to generate the fetch code record",
        19 => "Action fail",
        20 => "Pickup code in use",
        21 => "This order number has been submitted to lock stock, please do not repeat submission",
        22 => "Under stock",
        23 => "Invalid trade_no",
        24 => "Order processed",
        25 => "The order payment type does not match",
        99 => "Service Unavailable",
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

];