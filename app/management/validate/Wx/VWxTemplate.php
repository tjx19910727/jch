<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 16:14
 */

namespace app\management\validate\Wx;


use app\management\validate\VCommon;

class VWxTemplate extends VCommon
{

        protected $rule = [
            "wt_id" => "require",
            "wx_id" => "require",
            "template_id" => "require",
            "body" => "require",


        ];

        protected $message = [
            "wt_id.require" => "VWxTemplate.wt_id_require",
            "wx_id.require" => "VWxTemplate.wx_id_require",
            "template_id.require" => "VWxTemplate.template_id_require",
            "body.require" => "VWxTemplate.body_require",

        ];

        protected $scene = [
            "add" => ["wx_id","template_id","body"],
            "update" => ["wt_id"],
            "del" => ["wt_id"],
        ];
}