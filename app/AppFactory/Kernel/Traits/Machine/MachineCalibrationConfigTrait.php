<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/31
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineCalibrationConfigModel;

trait MachineCalibrationConfigTrait
{
    public function getMachineCalibrationConfigValue($where, $field, $order = "")
    {
        return MachineCalibrationConfigModel::getFieldValue($where, $field, $order);
    }

    public function getMachineCalibrationConfigFind($where, $field = "*", $order = "")
    {
        return MachineCalibrationConfigModel::getFind($where, $field, $order);
    }

    public function getMachineCalibrationConfigList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineCalibrationConfigModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineCalibrationConfig($insert)
    {
        if (isset($this->manager['manager_id'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = MachineCalibrationConfigModel::create($insert);
        return $data->id;
    }

    public function updateMachineCalibrationConfig($update, $where = [], $field = [])
    {
        if (isset($this->manager['manager_id'])) {
            $update['update_id'] = $this->manager['manager_id'];
        }
        return MachineCalibrationConfigModel::update($update, $where, $field);
    }

    public function delMachineCalibrationConfig($where)
    {
        return MachineCalibrationConfigModel::whereDel($where);
    }
}
