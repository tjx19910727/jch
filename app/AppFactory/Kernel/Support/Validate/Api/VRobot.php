<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 19:30
 */

namespace app\AppFactory\Kernel\Support\Validate\Api;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VRobot extends SupportValidate
{

        protected $rule = [
            "machine_id" => "require",
            "status" => "require",
        ];

        protected $message = [
            "machine_id.require" => "VRobot.machine_id_require",
            "status.require" => "VRobot.status_require",
        ];

        protected $scene = [
            "re_out_port" => ["machine_id"],
            "robot_go_charge" => ["machine_id","status"],
        ];
}
