<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 15:43
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthManagerModel extends BaseModel
{
    protected $name = "auth_manager";
    protected $pk = "manager_id";
    protected $schema = [
        "manager_id" => "int",
        "nickname" => "string",
        "account" => "string",
        "password" => "string",
        "pid" => "int",
        "level" => "int",
        "user_id" => "int",
        "openid" => "string",
        "sex" => "int",
        "pic" => "string",
        "balance" => "float",
        "frozen" => "float",
        "withdrawal" => "float",
        "bill_account" => "string",
        "real_name" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];


}