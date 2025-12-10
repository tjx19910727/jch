<?php
/**
 * Created by VSCode.
 * User: Alex-jixinag
 * Date: 2025/12/08
 * Time: 16:54
 */

namespace app\management\validate\Mall;


use app\management\validate\VCommon;

class VMall extends VCommon
{
    protected $rule = [
        "mall_id" => "require",
        "type" => "in:1,2,3",
        "status" => "in:1,2",
    ];

    protected $message = [
        "mall_id.require" => "VMall.mall_id_require",
        "mall_name.require" => "VMall.mall_name_require",
        "type.in" => "VMachine.type_in",
        "status.in" => "VMachine.status_in",
    ];

    protected $scene = [
        "add" => ["mall_name",'type','status'],
        "update" => ["mall_id"],
        "del" => ["mall_id"],
    ];
}