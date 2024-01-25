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
    protected $pk = "g_id";

    protected $schema = [
        "g_id" => "int",
        "g_name" => "string",
        "gc_id" => "int",
        "gc_name" => "string",
        "model" => "string",
        "bar_code" => "string",
        "sku" => "string",
        "sku2" => "string",
        "pic" => "string",
        "cost_price" => "float",
        "market_price" => "float",
        "retail_price" => "float",
        "manufacturer" => "string",
        "service_phone" => "string",
        "desc" => "string",
        "performance" => "string",
        "sell_channel" => "int",
        "expire_notice" => "int",
        "length" => "int",
        "width" => "int",
        "height" => "int",
        "group_quantity" => "int",
        "status" => "int",
        "ao_id" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

}