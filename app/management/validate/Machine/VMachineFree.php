<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 16:10
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineFree extends VCommon
{

        protected $rule = [
            "mf_id" => "require",
            "m_id" => "require",
            "machine_id" => "require",
            "machine_name" => "require",
            "free_status" => "require",
            "designated_hotel" => "require",
            "designated_goods" => "require",

            "mfh_id" => "require",
            "tc_id" => "require",
            "hotelId" => "require",

            "mfg_id" => "require",
            "g_id" => "require",
            "sale_amount" => "require",
        ];

        protected $message = [
            "mf_id.require" => "VMachineFree.mf_id_require",
            "m_id.require" => "VMachineFree.m_id_require",
            "machine_id.require" => "VMachineFree.machine_id_require",
            "machine_name.require" => "VMachineFree.machine_name_require",
            "free_status.require" => "VMachineFree.free_status_require",
            "designated_hotel.require" => "VMachineFree.designated_hotel_require",
            "designated_goods.require" => "VMachineFree.designated_goods_require",

            "mfh_id.require" => "VMachineFree.mfh_id_require",
            "tc_id.require" => "VMachineFree.tc_id_require",
            "hotelId.require" => "VMachineFree.hotelId_require",
            "mfg_id.require" => "VMachineFree.mfg_id_require",
            "g_id.require" => "VMachineFree.g_id_require",
            "sale_amount.require" => "VMachineFree.sale_amount_require",
        ];

        protected $scene = [
            "add" => ["m_id","machine_id","machine_name","free_status","designated_hotel","designated_goods"],
            "update" => ["mf_id"],
            "del" => ["mf_id"],
            "addHotelList" => ["tc_id","hotelId"],
            "addGoodsList" => ["g_id","sale_amount"],
        ];
}