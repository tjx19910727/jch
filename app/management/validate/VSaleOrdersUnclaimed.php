<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/22
 * Time: 17:07
 */

namespace app\management\validate;


class VSaleOrdersUnclaimed extends VCommon
{
    protected $rule = [
        "su_id" => "require",
        "status" => "require",
        "remark" => "max:255",
    ];
    protected $message = [
        "su_id.require" => "VSaleOrdersUnclaimed.su_id_require",
        "status.require" => "VSaleOrdersUnclaimed.status_require",
        "remark.max" => "VSaleOrdersUnclaimed.remark_max",
    ];
    protected $scene = [
        "operation" => ["su_id","status",'remark'],
    ];
}