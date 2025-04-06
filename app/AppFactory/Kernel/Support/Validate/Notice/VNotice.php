<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/6
 * Time: 11:07
 */

namespace app\AppFactory\Kernel\Support\Validate\Notice;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VNotice extends SupportValidate
{

        protected $rule = [
            "ao_id" => "require",
            "sendType" => "require|in:1,2",
            "templateType" => "require",
        ];

        protected $message = [
            "ao_id.require" => "VNotice.ao_id_require",
            "sendType.require" => "VNotice.sendType_require",
            "sendType.in" => "VNotice.sendType_in",
            "templateType.require" => "VNotice.templateType_require",
        ];

        protected $scene = [
            "getConfig" => ["ao_id","sendType","templateType"],
        ];
}