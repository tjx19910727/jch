<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/9
 * Time: 15:16
 */

namespace app\management\validate\MicroMall;


use app\management\validate\VCommon;

class VMicroMall extends VCommon
{

        protected $rule = [
            "mm_id" => "require",
            "mall_name" => "require",
        ];

        protected $message = [
            "mm_id.require" => "VMicroMall.mm_id_require",
            "mall_name.require" => "VMicroMall.mall_name_require",
        ];

        protected $scene = [
            "add" => ["mall_name"],
            "update" => ["mm_id"],
            "del" => ["mm_id"],
        ];
}