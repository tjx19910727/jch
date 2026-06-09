<?php

namespace app\AppFactory\Kernel\Model\Auth;

use app\AppFactory\Kernel\Model\BaseModel;

class AuthRoleTemplateNodeModel extends BaseModel
{
    protected $name = "auth_role_template_node";
    protected $pk = "artn_id";

    protected $schema = [
        "artn_id" => "int",
        "art_id" => "int",
        "node_id" => "int",
        "d_type" => "int",
        "is_del" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}
