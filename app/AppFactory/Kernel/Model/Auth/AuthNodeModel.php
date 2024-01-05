<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:08
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthNodeModel extends BaseModel
{
    protected $name = "auth_node";
    protected $pk = "node_id";

    protected $schema = [
        "node_id" => "int",
        "pid" => "int",
        "name" => "string",
        "icon" => "string",
        "url" => "string",
        "desc" => "string",
        "sort" => "int",
        "type" => "int",
        "is_auth" => "int",
        "status" => "int",
        "create_time" => "int",
        "update_time" => "int",
    ];
}