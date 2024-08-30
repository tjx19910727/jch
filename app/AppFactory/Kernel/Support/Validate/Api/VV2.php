<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 19:30
 */

namespace app\AppFactory\Kernel\Support\Validate\Api;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VV2 extends SupportValidate
{

        protected $rule = [
            "machine_id" => "require",
            "shelf_on" => "require",
            "pageNum" => "require",

            "kiosk_id" => "require",
            "order_no" => "require",
            "payment_method" => "require",
            "expire_time" => "require",
            "charge_time" => "require",
            "order_detail" => "require",

            "quantity" => "require",
            "item_price" => "require",
            "discount_amount" => "require",
            "charge_amount" => "require",
            "type" => "require",

            "pay_status" => "require",
            "reservation_status" => "require",
        ];

        protected $message = [
            "machine_id.require" => "machine_id_require",
            "shelf_on.require" => "shelf_on_require",
            "pageNum.require" => "pageNum_require",

            "kiosk_id.require" => "reserve_order.kiosk_id_require",
            "order_no.require" => "reserve_order.order_no_require",
            "payment_method.require" => "reserve_order.payment_method_require",
            "expire_time.require" => "reserve_order.expire_time_require",
            "charge_time.require" => "reserve_order.charge_time_require",
            "order_detail.require" => "reserve_order.order_detail_require",

            "quantity.require" => "order_detail.quantity_require",
            "item_price.require" => "order_detail.item_price_require",
            "discount_amount.require" => "order_detail.discount_amount_require",
            "charge_amount.require" => "order_detail.charge_amount_require",
            "type.require" => "order_detail.type_require",

            "pay_status.require" => "payNotify.pay_status_require",
            "reservation_status.require" => "hotelNotify.reservation_status_require",
        ];

        protected $scene = [
            "get_inventory_list" => ["machine_id","shelf_on","pageNum"],
            "get_machines" => ["pageNum"],
            "reserve_order" => ["kiosk_id","order_no","payment_method","expire_time","charge_time","order_detail"],
            "order_detail" => ["quantity","item_price","discount_amount","charge_amount","type"],
            "cancel_order" => ["kiosk_id","order_no"],
            "get_order_info" => ["kiosk_id","order_no"],
            "payNotify" => ["order_no","pay_status"],
            "hotelNotify" => ["order_no","reservation_status"],
        ];
}