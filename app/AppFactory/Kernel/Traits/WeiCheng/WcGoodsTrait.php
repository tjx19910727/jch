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
use app\AppFactory\Kernel\Model\WeiCheng\WcGoodsLocalModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcUserAddressesModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMachineChannelModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMachineGoodsModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMcSortLogModel;
use app\AppFactory\Kernel\Model\WeiCheng\WcMcSortLogDetailModel;

trait WcGoodsTrait
{
    public function getWcGoodsColumn($where, $field = '*', $order = '')
    {
        return WcGoodsModel::getColumn($where, $field, $order);
    }

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

    public function getWcGoodsLocalColumn($where, $column)
    {
        return WcGoodsLocalModel::getColumn($where, $column);
    }

    public function getWcGoodsLocalCount($where, $field = '*', $order = '')
    {
        return WcGoodsLocalModel::getFind($where, $field, $order);
    }

    public function getWcGoodsLocalList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcGoodsLocalModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcGoodsLocalFind($where, $field = "*", $order = "")
    {
        return WcGoodsLocalModel::getFind($where, $field, $order);
    }

    public function getWcGoodsLocalSum($where, $sum)
    {
        return WcGoodsLocalModel::getSum($where, $sum);
    }

    public function addWcGoodsLocal($insert)
    {
        $data = WcGoodsLocalModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcGoodsLocal($update, $where = [], $field = [])
    {
        return WcGoodsLocalModel::update($update, $where, $field);
    }

    public function delWcGoodsLocal($where)
    {
        return WcGoodsLocalModel::whereDel($where);
    }

    public function getWcMachineChannelList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMachineChannelModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcMachineChannelFind($where, $field = "*", $order = "")
    {
        return WcMachineChannelModel::getFind($where, $field, $order);
    }

    public function getWcMachineChannelSum($where, $sum)
    {
        return WcMachineChannelModel::getSum($where, $sum);
    }

    public function addWcMachineChannel($insert)
    {
        $data = WcMachineChannelModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function addWcMachineChannelMore($insertAll)
    {
        $model = new WcMachineChannelModel();
        return $model->saveAll($insertAll);
    }

    public function updateWcMachineChannel($update, $where = [], $field = [])
    {
        return WcMachineChannelModel::update($update, $where, $field);
    }

    public function delWcMachineChannel($where)
    {
        return WcMachineChannelModel::whereDel($where);
    }

    public function getWcMachineGoodsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMachineGoodsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcMachineGoodsFind($where, $field = "*", $order = "")
    {
        return WcMachineGoodsModel::getFind($where, $field, $order);
    }

    public function getWcMachineGoodsSum($where, $sum)
    {
        return WcMachineGoodsModel::getSum($where, $sum);
    }

    public function addWcMachineGoods($insert)
    {
        $data = WcMachineGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function addWcMachineGoodsMore($insertAll)
    {
        $model = new WcMachineGoodsModel();
        return $model->saveAll($insertAll);
    }

    public function updateWcMachineGoods($update, $where = [], $field = [])
    {
        return WcMachineGoodsModel::update($update, $where, $field);
    }

    public function delWcMachineGoods($where)
    {
        return WcMachineGoodsModel::whereDel($where);
    }

    public function getWcUserAddressesList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcUserAddressesModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcUserAddressesFind($where, $field = "*", $order = "")
    {
        return WcUserAddressesModel::getFind($where, $field, $order);
    }

    public function addWcUserAddresses($insert)
    {
        $data = WcUserAddressesModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function addWcUserAddressesMore($insertAll)
    {
        $model = new WcUserAddressesModel();
        return $model->saveAll($insertAll);
    }

    public function updateWcUserAddresses($update, $where = [], $field = [])
    {
        return WcUserAddressesModel::update($update, $where, $field);
    }

    public function delWcUserAddresses($where)
    {
        return WcUserAddressesModel::whereDel($where);
    }

    // ========== wc_mc_sort_log ==========

    public function getWcMcSortLogList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMcSortLogModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcMcSortLogFind($where, $field = "*", $order = "")
    {
        return WcMcSortLogModel::getFind($where, $field, $order);
    }

    public function addWcMcSortLog($insert)
    {
        $data = WcMcSortLogModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    // ========== wc_mc_sort_log_detail ==========

    public function getWcMcSortLogDetailList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcMcSortLogDetailModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addWcMcSortLogDetailMore($insertAll)
    {
        $model = new WcMcSortLogDetailModel();
        return $model->saveAll($insertAll);
    }

    public function test(){
        //测试解决冲突
    }   
        
}
