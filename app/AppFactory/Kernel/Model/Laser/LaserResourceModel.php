<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/28
 * Time: 14:30
 */

namespace app\AppFactory\Kernel\Model\Laser;


use app\AppFactory\Kernel\Model\BaseModel;

class LaserResourceModel extends BaseModel
{
    protected $pk = "res_id";
    protected $name = "laser_resource";
    protected $schema = [
        "res_id" => 'int',
        "file_path" => 'string',
        "type" => 'int',
        "file_name" => 'string',
        "desc" => 'string',
        "length" => 'int',
        "width" => 'int',
        "size" => 'int',
        "order_id" => 'int',
        "create_time" => 'int',
        "trade_no" => 'string',
    ];
}