<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:06
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthRoleModel extends BaseModel
{
    protected $name = "auth_role";
    protected $pk = "role_id";

    protected $schema = [
        "role_id" => "int",
        "name" => "string",
        "desc" => "string",
        "sort" => "int",
        "status" => "int",
        'ao_id' => 'int',
        "template_id" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}
