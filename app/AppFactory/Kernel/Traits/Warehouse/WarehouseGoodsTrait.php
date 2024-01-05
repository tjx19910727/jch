<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 8:59
 */

namespace app\AppFactory\Kernel\Traits\Warehouse;


use app\AppFactory\Kernel\Model\Warehouse\WarehouseGoodsModel;

trait WarehouseGoodsTrait
{

    /**
     * 获取某个仓库或指定的仓库商品总仓库
     * @param $where
     * @param $sum
     * @return float
     */
    public function getWarehouseGoodsSum($where,$sum)
    {
        return WarehouseGoodsModel::getSum($where,$sum);
    }

    public function getWarehouseGoodsFind($where,$field = "*",$order = "")
    {
        return WarehouseGoodsModel::getFind($where,$field,$order);
    }

    public function getWarehouseGoodsValue($where,$value)
    {
        return WarehouseGoodsModel::getFieldValue($where,$value);
    }

    public function getWarehouseGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        $result = WarehouseGoodsModel::getList($where,$pageNum,$field,$order,$eachFun);
        return $result;
    }

    public function addWarehouseGoods($insert)
    {
        $data = WarehouseGoodsModel::create($insert);
        return $data->wg_id;
    }

    public function updateWarehouseGoods($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return WarehouseGoodsModel::update($update,$where,$field);
    }

    public function delWarehouseGoods($where)
    {
        return WarehouseGoodsModel::destroy($where);
    }

    public function incWarehouseGoods($where,$field,$inc)
    {
        return WarehouseGoodsModel::setInc($where,$field,$inc);
    }

    public function decWarehouseGoods($where,$field,$inc)
    {
        return WarehouseGoodsModel::setDec($where,$field,$inc);
    }
}