<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 14:42
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthOrganizationModel extends BaseModel
{
    protected $pk = "ao_id";
    protected $name = "auth_organization";

    protected $schema = [
        "ao_id" => "int",
        "pid" => "int",
        "level" => "int",
        "organization_name" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}