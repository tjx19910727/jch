<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 10:04
 */
return [
    "captcha" => [
        "get_success" => "获取验证码成功",
    ],

    // VAuth
    "VAuth" => [
        "name_require" => "节点名称不能为空",
        "manager_id_require" => "请选择管理员账号",
        "account_require" => "账号不能为空",
        "account_unique" => "账号已存在，请勿重复添加",
        "password_require" => "密码不能为空",
        "status_require" => "状态不能为空",
        "status_in" => "状态超出范围",
        "mr_id_require" => "管理员关联角色ID不能为空",
        "role_id_require" => "权限角色ID不能为空",
        "type_require" => "节点类型不能为空",
        "nodeList_require" => "节点列表不能为空",
        "rn_id_require" => "权限角色关联节点不能为空",
        "list_require" => "通知开关数据不能为空",
        "notice_type_require" => "开关类型不能为空",

        // auth_organization
        "ao_id_require" => '组织ID不能为空',
        "pid_require" => '上级组织ID不能为空',
        "organization_name_require" => '组织名称不能为空',

        "roleList_require" => "权限角色ID不能为空",
    ],
];