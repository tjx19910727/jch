<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:45
 */

namespace app\management\validate\Email;


use app\management\validate\VCommon;

class VEmailTemplate extends VCommon
{

        protected $rule = [
            "et_id" => "require",
            "subject" => "require",
            "body" => "require",
            "attachment" => "max:1000",
            "template_type" => "require",
        ];

        protected $message = [
            "et_id.require" => "VEmailTemplate.et_id_require",
            "subject.require" => "VEmailTemplate.subject_require",
            "body.require" => "VEmailTemplate.body_require",
            "attachment.max" => "VEmailTemplate.attachment_max",
            "template_type.require" => "VEmailTemplate.template_type_require",
        ];

        protected $scene = [
            "add" => ["subject","body","attachment","template_type"],
            "update" => ["et_id"],
            "del" => ["et_id"],
        ];
}