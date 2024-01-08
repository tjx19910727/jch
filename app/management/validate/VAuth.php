<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 9:32
 */

namespace app\management\validate;


class VAuth extends VCommon
{
    protected $rule = [
        "name" => "require",
        // auth_manager
        "manager_id" => 'require',
        "account" => 'require|unique:auth_manager',
        "password" => 'require',
        "status" => 'require|in:1,2',

        // auth_manager_role
        "mr_id" => 'require',
        "role_id" => 'require',

        // auth_node
        "type" => "require",

        // auth_role_node
        "nodeList" => "require",

        "list" => "require",
        "notice_type" => "require",
        "store_id" => "require",


        "rn_id" => "require",

        "ao_id" => "require",
        "pid" => "require",
        "organization_name" => "require",

        "roleList" => "require",

    ];

    protected $message = [
        "name.require" => "name_require",
        "manager_id.require" => "manager_id_require",
        "account.require" => "account_require",
        "account.unique" => "account_unique",
        "password.require" => "password_require",
        "status.require" => "status_require",
        "status.in" => "status_in",
        "mr_id.require" => "mr_id_require",
        "role_id.require" => "role_id_require",
        "type.require" => "type_require",
        "nodeList.require" => "nodeList_require",
        "rn_id.require" => "rn_id_require",

        'list.require' => 'list_require',
        'notice_type.require' => 'notice_type_require',

        "ao_id.require" => "auth_organization.ao_id_require",
        "pid.require" => "auth_organization.pid_require",
        "organization_name.require" => "auth_organization.organization_name_require",


        "roleList.require" => "auth_organization.roleList_require",
    ];

    protected $scene = [
        "AuthManagerAdd" => ["account","password","status"],
        "AuthManagerUpdate" => ["manager_id","status"],
        "UpdatePassword" => ["manager_id","password"],

        "AuthManagerNotice" => ["manager_id","list"],
        "AuthManagerNoticeList" => ["notice_type","status"],

        "AuthManagerRoleAdd" => ["manager_id","role_id"],
        "AuthManagerRoleUpdate" => ["mr_id"],

        "AuthNodeAdd" => ["name","type"],
        "AuthNodeUpdate" => ["node_id"],

        "AuthRoleAdd" => ["name","status"],
        "AuthRoleUpdate" => ["role_id"],

        "AuthRoleNodeAdd" => ["role_id","nodeList"],
        "AuthRoleNodeUpdate" => ["rn_id"],

        "AuthOrganizationAdd" => ['pid','organization_name'],
        "AuthOrganizationUpdate" => ["or_id",'pid','organization_name'],

        "AuthOrganizationRoleBind" => ['ao_id','roleList'],

    ];
}