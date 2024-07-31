<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 17:01
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineErrorCodeSolution extends VCommon
{

        protected $rule = [
            "s_id" => "require",
            "error_code" => "require",
        ];

        protected $message = [
            "error_code.require" => "VMachineErrorCode.error_code_require",
        ];

        protected $scene = [
            "add" => ["error_code"],
            "update" => ["s_id"],
            "del" => ["s_id"],
        ];
}