<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/27
 * Time: 8:48
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineFreeGoods extends VCommon
{

        protected $rule = [
            "mfg_id" => "require",
            "g_id" => "require",
            "mf_id" => "require",
            "sale_amount" => "require",
        ];

        protected $message = [
            "mfg_id.require" => "VMachineFree.mfg_id_require",
            "g_id.require" => "VMachineFree.g_id_require",
            "mf_id.require" => "VMachineFree.mf_id_require",
            "sale_amount.require" => "VMachineFree.sale_amount_require",
        ];

        protected $scene = [
            "add" => ["g_id",'mf_id',"sale_amount"],
            "update" => ["mfg_id"],
            "del" => ["mfg_id"],
        ];
}