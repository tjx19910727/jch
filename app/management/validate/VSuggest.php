<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/17
 * Time: 10:11
 */

namespace app\management\validate;


class VSuggest extends VCommon
{

        protected $rule = [
            "s_id" => "require",
            "content" => "require|length:15,500",
            "pic" => "length:1024",
            "email" => "require",
        ];

        protected $message = [
            "s_id.require" => "VSuggest.s_id_require",
            "content.require" => "VSuggest.content_require",
            "content.length" => "VSuggest.content_length",
            "pic.length" => "VSuggest.pic_length",
            "email.require" => "VSuggest.email_require",
        ];

        protected $scene = [
            "add" => ["content","pic","email"],
            "del" => ["s_id"],
        ];
}