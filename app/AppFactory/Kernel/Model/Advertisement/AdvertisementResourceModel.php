<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/6
 * Time: 16:55
 */

namespace app\AppFactory\Kernel\Model\Advertisement;


use app\AppFactory\Kernel\Model\BaseModel;

class AdvertisementResourceModel extends BaseModel
{
    protected $pk = "res_id";
    protected $name = "advertisement_resource";

    protected $schema = [
        "res_id" => "int",
        "title" => "string",
        "file_path" => "string",
        "type" => "int",
        "file_name" => "string",
        "desc" => "string",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}