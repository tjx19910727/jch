<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 9:11
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsMultipleGoodsModel extends BaseModel
{
    protected $pk = "gmg_id";
    protected $name = "goods_multiple_goods";

    public static function getJoinGoodsList($where,$field = "*", $order = "")
    {
        return self::alias("gmg")
            ->join("goods g","gmg.g_id = g.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
    }

    public static function getJoinGoodsFind($where,$field = "*",$order = "")
    {
        return self::alias("gmg")
            ->join("goods g","gmg.g_id = g.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
    }
}