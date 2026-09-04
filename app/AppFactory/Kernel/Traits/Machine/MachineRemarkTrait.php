<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/19
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineRemarkModel;

/**
 * 设备备注记录
 */
trait MachineRemarkTrait
{
    public function getMachineRemarkValue($where, $value, $order = "")
    {
        return MachineRemarkModel::getFieldValue($where, $value, $order);
    }

    public function getMachineRemarkCount($where)
    {
        return MachineRemarkModel::getCount($where);
    }

    public function getMachineRemarkFind($where, $field = "*", $order = "")
    {
        return MachineRemarkModel::getFind($where, $field, $order);
    }

    public function getMachineRemarkList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineRemarkModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineRemark($insert)
    {
        if (!isset($insert['create_time'])) {
            $insert['create_time'] = time();
        }
        if (!isset($insert['update_time'])) {
            $insert['update_time'] = time();
        }
        $data = MachineRemarkModel::create($insert);
        return $data->id;
    }

    public function updateMachineRemark($update, $where = [], $field = [])
    {
        if (!isset($update['update_time'])) {
            $update['update_time'] = time();
        }
        return MachineRemarkModel::update($update, $where, $field);
    }

    public function delMachineRemark($where)
    {
        return MachineRemarkModel::whereDel($where);
    }
}
