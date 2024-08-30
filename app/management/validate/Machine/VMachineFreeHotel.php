<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/27
 * Time: 8:48
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineFreeHotel extends VCommon
{

    protected $rule = [
        "mfh_id" => "require",
        "mf_id" => "require",
        "tc_id" => "require",
        "hotelId" => "require",
    ];

    protected $message = [
        "mfh_id.require" => "VMachineFree.mfh_id_require",
        "tc_id.require" => "VMachineFree.tc_id_require",
        "hotelId.require" => "VMachineFree.hotelId_require",
    ];

    protected $scene = [
        "add" => ["mf_id","tc_id","hotelId"],
        "update" => ["mfh_id"],
        "del" => ["mfh_id"],
    ];
}