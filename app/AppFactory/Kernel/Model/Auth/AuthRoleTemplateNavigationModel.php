<?php

namespace app\AppFactory\Kernel\Model\Auth;

use app\AppFactory\Kernel\Model\BaseModel;

class AuthRoleTemplateNavigationModel extends BaseModel
{
    protected $name = "auth_role_template_navigation";
    protected $pk = "artnavi_id";

    protected $schema = [
        "artnavi_id" => "int",
        "art_id" => "int",
        "node_id" => "int",
        "data_scope" => "string",
        "create_enabled" => "int",
        "delete_enabled" => "int",
        "update_enabled" => "int",
        "query_enabled" => "int",
        "is_del" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}
