<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/01/05
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsTypesModel;

trait WcGoodsTrait
{
    public function getWcGoodsCount($where, $field = '*', $order = '')
    {
        return WcGoodsModel::getFind($where, $field, $order);
    }

    public function getWcGoodsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsFind($where, $field = "*", $order = "")
    {
        return WcGoodsModel::getFind($where, $field, $order);
    }

    public function getWcGoodsSum($where, $sum)
    {
        return WcGoodsModel::getSum($where, $sum);
    }

    public function addWcGoods($insert)
    {
        $data = WcGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcGoods($update, $where = [], $field = [])
    {
        return WcGoodsModel::update($update, $where, $field);
    }

    public function delWcGoods($where)
    {
        return WcGoodsModel::whereDel($where);
    }

    public function getWcGoodsTypesCount($where, $field = '*', $order = '')
    {
        return WcGoodsTypesModel::getFind($where, $field, $order);
    }

    public function getWcGoodsTypesList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsTypesModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsTypesFind($where, $field = "*", $order = "")
    {
        return WcGoodsTypesModel::getFind($where, $field, $order);
    }

    public function getWcGoodsTypesSum($where, $sum)
    {
        return WcGoodsTypesModel::getSum($where, $sum);
    }

    public function addWcGoodsTypes($insert)
    {
        $data = WcGoodsTypesModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcGoodsTypes($update, $where = [], $field = [])
    {
        return WcGoodsTypesModel::update($update, $where, $field);
    }

    public function delWcGoodsTypes($where)
    {
        return WcGoodsTypesModel::whereDel($where);
    }
}
