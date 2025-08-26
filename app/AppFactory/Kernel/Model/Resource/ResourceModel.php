<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 10:45
 */

namespace app\AppFactory\Kernel\Model\Resource;


use app\AppFactory\Kernel\Model\BaseModel;

class ResourceModel extends BaseModel
{
    protected $pk = "res_id";
    protected $name = "resource";
    protected $schema = [
        "res_id" => 'int',
        "title" => 'string',
        "file_path" => 'int',
        "type" => 'int',
        "file_name" => 'int',
        "desc" => 'int',
        "length" => 'int',
        "width" => 'int',
        "size" => 'int',
        "status" => 'int',
        "ao_id" => 'int',
        "creator" => 'int',
        "create_time" => 'int',
        "update_id" => 'int',
        "update_time" => 'int',
    ];
}