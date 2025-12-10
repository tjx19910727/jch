<?php
/**
 * Created by VSCode.
 * User: Alex-jixinag
 * Date: 2025/12/08
 * Time: 16:54
 */

namespace app\management\validate\Mall;


use app\management\validate\VCommon;

class VMallMachine extends VCommon
{
    protected $rule = [
        "mall_id" => "require",
        "machine_id" => "require",
        "status" => "in:1,2,3",
    ];

    protected $message = [
        "mall_id.require" => "VMall.mall_id_require",
        "machine_id.require" => "VMall.machine_id_require",
        "status.in" => "VMachine.status_in",
    ];

    protected $scene = [
        "add" => ["mall_id",'machine_id','status'],
        "update" => ["mall_id"],
        "del" => ["mall_id"],
    ];
}