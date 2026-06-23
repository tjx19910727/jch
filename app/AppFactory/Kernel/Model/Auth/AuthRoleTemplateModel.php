<?php

namespace app\AppFactory\Kernel\Model\Auth;

use app\AppFactory\Kernel\Model\BaseModel;

class AuthRoleTemplateModel extends BaseModel
{
    protected $name = "auth_role_template";
    protected $pk = "art_id";

    protected $schema = [
        "art_id" => "int",
        "name" => "string",
        "desc" => "string",
        "ao_id" => "int",
        "status" => "int",
        "is_del" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}
