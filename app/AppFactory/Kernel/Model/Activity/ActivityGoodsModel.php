<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:22
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityGoodsModel extends BaseModel
{
    protected $pk = "ag_id";
    protected $name = "activity_goods";
    protected $schema = [
        "ag_id" => "int",
        "a_type" => "int",
        "a_id" => "int",
        "store_id" => "int",
        "ss_id" => "int",
        "shelves_number" => "string",
        "wg_id" => "int",
        "goods_id" => "int",
        "goods_name" => "string",
        "goods_pic" => "string",
        "goods_c_id" => "int",
        "goods_c_name" => "string",
    ];
}