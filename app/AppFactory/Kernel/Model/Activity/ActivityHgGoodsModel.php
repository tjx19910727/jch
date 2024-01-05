<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/6
 * Time: 11:39
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityHgGoodsModel extends BaseModel
{
    protected $pk = "ahg_id";
    protected $name = "activity_hg_goods";
    protected $schema = [
        "ahg_id" => "int",
        "ah_id" => "int",
        "full_order_amount" => "float",
        "wg_id" => "int",
        "goods_id" => "int",
        "goods_name" => "string",
        "goods_pic" => "string",
        "goods_c_id" => "int",
        "goods_c_name" => "string",
        "amount" => "float",
    ];
}