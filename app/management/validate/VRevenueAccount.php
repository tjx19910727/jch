<?php

namespace app\management\validate;

class VRevenueAccount extends VCommon
{
    protected $rule = [
        "ra_id" => "require",
        "ao_id" => "require",
        "manager_id" => "require",
        "account_name" => "require",
        "account_type" => "require",
        "status" => "require",
    ];

    protected $message = [
        "ra_id.require" => "分账账户ID不能为空",
        "ao_id.require" => "所属组织不能为空",
        "manager_id.require" => "账户管理人不能为空",
        "account_name.require" => "账户名称不能为空",
        "account_type.require" => "账户类型不能为空",
        "status.require" => "状态不能为空",
    ];

    protected $scene = [
        "add" => ["ao_id", "manager_id", "account_name", "account_type"],
        "update" => ["ra_id"],
    ];
}
