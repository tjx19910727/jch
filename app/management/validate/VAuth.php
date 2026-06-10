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
        "old_pwd" => 'require',
        "status" => 'require|in:1,2',

        // auth_manager_role
        "mr_id" => 'require',
        "role_id" => 'require',
        "manager_ids" => 'require',
        "use_role_template" => 'in:1,2',
        "art_id" => 'require',

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

        "m_ids" => "require",
    ];

    protected $message = [
        "name.require" => "VAuth.name_require",
        "manager_id.require" => "VAuth.manager_id_require",
        "account.require" => "VAuth.account_require",
        "account.unique" => "VAuth.account_unique",
        "password.require" => "VAuth.password_require",
        "old_pwd.require" => "VAuth.old_pwd_require",
        "status.require" => "VAuth.status_require",
        "status.in" => "VAuth.status_in",
        "mr_id.require" => "VAuth.mr_id_require",
        "role_id.require" => "VAuth.role_id_require",
        "manager_ids.require" => "账号ID列表不能为空",
        "use_role_template.in" => "是否使用角色权限模板仅支持1或2",
        "art_id.require" => "角色权限模板ID不能为空",
        "type.require" => "VAuth.type_require",
        "nodeList.require" => "VAuth.nodeList_require",
        "rn_id.require" => "VAuth.rn_id_require",

        'list.require' => 'VAuth.list_require',
        'notice_type.require' => 'VAuth.notice_type_require',

        "ao_id.require" => "VAuth.ao_id_require",
        "pid.require" => "VAuth.pid_require",
        "organization_name.require" => "VAuth.organization_name_require",


        "roleList.require" => "VAuth.roleList_require",


        "m_ids.require" => "VAuth..m_ids_require",
    ];

    protected $scene = [
        "AuthManagerAdd" => ["account","password","status","use_role_template"],
        "AuthManagerUpdate" => ["manager_id","status","use_role_template"],
        "UpdatePassword" => ["manager_id","password"],
        "UpdateSelfPwd" => ["old_pwd","password"],

        "AuthManagerMachine_bind" => ["manager_id","m_ids"],

        "AuthManagerNotice" => ["manager_id","list"],
        "AuthManagerNoticeList" => ["notice_type","status"],

        "AuthManagerRoleAdd" => ["manager_id","role_id"],
        "AuthManagerRoleUpdate" => ["mr_id"],
        "AuthManagerRoleBatchSet" => ["role_id"],

        "AuthNodeAdd" => ["name","type"],
        "AuthNodeUpdate" => ["node_id"],

        "AuthRoleAdd" => ["name","status"],
        "AuthRoleUpdate" => ["role_id"],

        "AuthRoleNodeAdd" => ["role_id","nodeList"],
        "AuthRoleNodeUpdate" => ["rn_id"],

        "AuthRoleTemplateAdd" => ["name","status"],
        "AuthRoleTemplateUpdate" => ["art_id"],
        "AuthRoleTemplateNodes" => ["art_id","nodeList"],
        "AuthRoleTemplateApply" => ["art_id","role_id"],

        "AuthOrganizationAdd" => ['pid','organization_name'],
        "AuthOrganizationUpdate" => ["or_id",'pid','organization_name'],

        "AuthOrganizationRoleBind" => ['ao_id'],

    ];
}
