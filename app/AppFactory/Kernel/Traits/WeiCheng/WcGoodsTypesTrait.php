<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/01/05
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsTypesModel;

trait WcGoodsTypesTrait
{
    public function getWcGoodsTypesCount($where, $field = '*', $order = '')
    {
        return WcGoodsTypesModel::getFind($where, $field, $order);
    }

    public function getWcGoodsTypesList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsTypesModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsTypesFind($where,$field = "*",$order = "")
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

    public function synchronizeGoodsTypes()
    {
        return ['code' => 200, 'message' => 'Synchronization successful'];
    }
}
