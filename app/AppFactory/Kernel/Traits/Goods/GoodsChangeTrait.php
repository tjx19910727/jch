<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/17
 * Time: 15:25
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsChangeModel;

trait GoodsChangeTrait
{

    public function getGoodsChangeCount($where)
    {
        return GoodsChangeModel::getCount($where);
    }

    public function getGoodsChangeValue($where,$value)
    {
        return GoodsChangeModel::getFieldValue($where,$value);
    }

    public function getGoodsChangeColumn($where,$column)
    {
        return GoodsChangeModel::getColumn($where,$column);
    }

    public function getGoodsChangeFind($where,$field = "*",$order = "")
    {
        return GoodsChangeModel::getFind($where,$field,$order);
    }

    public function getGoodsChangeList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '')
    {
        return GoodsChangeModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function addGoodsChange($insert)
    {
        $data = GoodsChangeModel::create($insert);
        return $data->change_id;
    }

    public function updateGoodsChange($update,$where = [],$field = [])
    {
        return GoodsChangeModel::update($update,$where,$field);
    }

    public function delGoodsChange($where)
    {
        $result = GoodsChangeModel::whereDel($where);
        return $result;
    }

}