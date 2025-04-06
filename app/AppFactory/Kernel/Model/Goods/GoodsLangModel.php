<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/17
 * Time: 19:59
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsLangModel extends BaseModel
{
    protected $pk = "gl_id";
    protected $name = "goods_lang";

    protected $schema = [
        "gl_id" => "int",
        "g_id" => "int",
        "g_name" => "string",
        "gc_id" => "int",
        "gc_name" => "string",
        "manufacturer" => "string",
        "desc" => "string",
        "performance" => "string",
        "lang" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}