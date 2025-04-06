<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 8:58
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsCategoryLangModel extends BaseModel
{
    protected $pk = "gcl_id";
    protected $name = "goods_category_lang";

    protected $schema = [
        "gcl_id" => "int",
        "gc_id" => "int",
        "gc_name" => "string",
        "desc" => "string",
        "lang" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}