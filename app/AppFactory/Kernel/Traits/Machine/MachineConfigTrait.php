<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:36
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineConfigModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;

trait MachineConfigTrait
{


    public function getMachineConfigFind($where, $field = "*", $order = "")
    {
        return MachineConfigModel::getFind($where, $field, $order);
    }

    public function getMachineConfigList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineConfigModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineConfig($insert)
    {
        !isset($this->manager['manager_id']) ?: $insert['creator'] = $this->manager['manager_id'];
        $data = MachineConfigModel::create($insert);
        $this->syncMachineRecycleBoxCapacity($insert, ['mc_id' => $data->mc_id]);
        return $data->mc_id;
    }

    public function updateMachineConfig($update, $where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        $result = MachineConfigModel::update($update, $where, $field);
        $this->syncMachineRecycleBoxCapacity($update, $where);
        return $result;
    }

    public function delMachineConfig($where)
    {
        $result = MachineConfigModel::whereDel($where);
        return $result;
    }

    protected function syncMachineRecycleBoxCapacity($data, $where = [])
    {
        if (!array_key_exists('recycle_bin_capacity', $data)) {
            return true;
        }

        $capacity = intval($data['recycle_bin_capacity']);
        if ($capacity < 0) {
            $capacity = 0;
        }

        $machineWhere = [];
        if (isset($data['m_id']) && $data['m_id']) {
            $machineWhere['m_id'] = intval($data['m_id']);
        } elseif (isset($where['m_id']) && $where['m_id']) {
            $machineWhere['m_id'] = intval($where['m_id']);
        } elseif (isset($data['machine_id']) && $data['machine_id']) {
            $machineWhere['machine_id'] = $data['machine_id'];
        } elseif (isset($where['machine_id']) && $where['machine_id']) {
            $machineWhere['machine_id'] = $where['machine_id'];
        } else {
            $configWhere = [];
            if (isset($data['mc_id']) && $data['mc_id']) {
                $configWhere['mc_id'] = intval($data['mc_id']);
            } elseif (isset($where['mc_id']) && $where['mc_id']) {
                $configWhere['mc_id'] = intval($where['mc_id']);
            }
            if ($configWhere) {
                $config = MachineConfigModel::getFind($configWhere, 'm_id,machine_id');
                if ($config) {
                    $config = is_object($config) ? $config->toArray() : $config;
                    if (!empty($config['m_id'])) {
                        $machineWhere['m_id'] = intval($config['m_id']);
                    } elseif (!empty($config['machine_id'])) {
                        $machineWhere['machine_id'] = $config['machine_id'];
                    }
                }
            }
        }

        if (!$machineWhere) {
            actionLog(['data' => $data, 'where' => $where], '同步回收箱总容量缺少设备定位信息');
            return false;
        }

        $machine = MachineModel::getFind($machineWhere, 'm_id,recycle_box_total_capacity,recycle_box_remain_capacity');
        if (!$machine) {
            actionLog($machineWhere, '同步回收箱总容量未找到设备');
            return false;
        }
        $machine = is_object($machine) ? $machine->toArray() : $machine;
        $update = [
            'm_id' => $machine['m_id'],
            'recycle_box_total_capacity' => $capacity,
            'recycle_box_remain_capacity' => $capacity,
        ];

        return MachineModel::update($update);
    }
}
