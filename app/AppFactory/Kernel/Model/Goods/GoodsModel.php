<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:59
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsModel extends BaseModel
{
    protected $name = "goods";
    protected $pk = "goods_id";

    protected $schema = [
        "goods_id" => "int",
        "goods_name" => "string",
        "pic" => "string",
        "bar_code" => "string",
        "cost_price" => "float",
        "retail_price" => "float",
        "sell_by_date" => "int",
        "is_public" => "int",
        "status" => "int",
        "gc_id" => "int",
        "gc_name" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

}