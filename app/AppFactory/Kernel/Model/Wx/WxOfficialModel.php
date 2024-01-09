<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/8
 * Time: 17:56
 */

namespace app\AppFactory\Kernel\Model\Wx;


use app\AppFactory\Kernel\Model\BaseModel;

class WxOfficialModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "wx_official";

    protected $schema = [
        "id" => "int",
        "gh_id" => "string",
        "wx_name" => "string",
        "app_id" => "string",
        "secret" => "string",
        "token" => "string",
        "aes_key" => "string",
        "wx_txt" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}