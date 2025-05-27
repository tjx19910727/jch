<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 13:46
 */

namespace app\AppFactory\Kernel\Model\User;


use app\AppFactory\Kernel\Model\BaseModel;

class UserModel extends BaseModel
{
    protected $pk = "user_id";
    protected $name = "user";

//    protected $schema = [
//        "user_id" => "int",
//        "unionid" => "string",
//        "openid" => "string",
//        "name" => "string",
//        "pic" => "string",
//        "gender" => "int",
//        "mobile" => "string",
//        "province" => "string",
//        "city" => "string",
//        "address" => "string",
//        "Latitude" => "string",
//        "Longitude" => "string",
//        "Precision" => "string",
//        "type" => "int",
//        "blacklist" => "int",
//        "manager_id" => "int",
//        "creator" => "int",
//        "create_time" => "int",
//        "update_id" => "int",
//        "update_time" => "int",
//    ];
}