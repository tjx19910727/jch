<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:59
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsCategoryModel extends BaseModel
{
    protected $pk = "gc_id";
    protected $name = "goods_category";

    protected $schema = [
        "gc_id" => "int",
        "gc_pid" => "int",
        "gc_name" => "string",
        "ico" => "string",
        "sort" => "int",
        "status" => "int",
        "desc" => "string",
        "ao_id" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}