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
        ];

        protected $message = [
            "machine_id.require" => "machine_id_require",
            "shelf_on.require" => "shelf_on_require",
        ];

        protected $scene = [
            "get_inventory_list" => ["machine_id","shelf_on"],
        ];
}