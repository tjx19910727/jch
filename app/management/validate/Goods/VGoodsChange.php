<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/18
 * Time: 16:21
 */

namespace app\management\validate\Goods;


use app\management\validate\VCommon;

class VGoodsChange extends VCommon
{

        protected $rule = [
            "create_time" => "require",
        ];

        protected $message = [
            "create_time.require" => "VGoodsChange.create_time_require",
        ];

        protected $scene = [
            "exportGc" => ["create_time"],
        ];
}