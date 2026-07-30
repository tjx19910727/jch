<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 19:10
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelSchemeModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelSchemeDetailModel;

trait MachineChannelSchemeTrait
{
    public function getMachineChannelSchemeFind($where, $field = "*", $order = "")
    {
        return MachineChannelSchemeModel::getFind($where, $field, $order);
    }

    public function getMachineChannelSchemeList($where, $pageNum = 0, $field = "*", $order = "mcs_id desc", $eachFun = "")
    {
        return MachineChannelSchemeModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function getMachineChannelSchemeCount($where)
    {
        return MachineChannelSchemeModel::getCount($where);
    }

    public function addMachineChannelScheme($insert)
    {
        $data = MachineChannelSchemeModel::create($insert);
        return $data->mcs_id;
    }

    public function updateMachineChannelScheme($update, $where = [], $field = [])
    {
        return MachineChannelSchemeModel::update($update, $where, $field);
    }

    public function delMachineChannelScheme($where)
    {
        return MachineChannelSchemeModel::whereDel($where);
    }

    // ========== 方案明细 ==========

    public function getMachineChannelSchemeDetailFind($where, $field = "*", $order = "")
    {
        return MachineChannelSchemeDetailModel::getFind($where, $field, $order);
    }

    public function getMachineChannelSchemeDetailList($where, $pageNum = 0, $field = "*", $order = "mcsd_id asc", $eachFun = "")
    {
        return MachineChannelSchemeDetailModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineChannelSchemeDetailAll($insertAll)
    {
        $model = new MachineChannelSchemeDetailModel();
        return $model->saveAll($insertAll);
    }

    public function delMachineChannelSchemeDetail($where)
    {
        return MachineChannelSchemeDetailModel::whereDel($where);
    }
}