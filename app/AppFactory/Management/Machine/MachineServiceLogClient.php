<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/4
 * Time: 10:02
 */

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Traits\Machine\MachineServiceLogTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineServiceLogClient extends ManagementClient
{
    use MachineTrait;
    use MachineServiceLogTrait;

    public function addLog($postData)
    {
        if ($postData['m_id'] > 0) {
            $postData['machine_id'] = $this->getMachineValue(['m_id' => $postData['m_id']], 'machine_id');
        }
        $result = $this->addMachineServiceLog($postData);
        return $this->rA($result);
    }

    public function updateLog($postData, $where = [], $field = [], $rU = 1)
    {
        if ($postData['m_id'] > 0) {
            $postData['machine_id'] = $this->getMachineValue(['m_id' => $postData['m_id']], 'machine_id');
        }
        $result = $this->updateMachineServiceLog($postData, $where, $field);
        return $this->rU($result);
    }

    public function delLog($where, $rD = 1)
    {
        $postData = $where;
        $id = $postData['id'] ?? '';
        if (!$id) {
            return $this->rValidate('id不能为空');
        }
        if (is_string($id) && strpos($id, ',') !== false) {
            $result = $this->delMachineServiceLog([['id', 'in', $id]]);
            return $rD ? $this->rD($result) : $result;
        }
        if (is_array($id)) {
            $result = $this->delMachineServiceLog([['id', 'in', $id]]);
            return $rD ? $this->rD($result) : $result;
        }
        $result = $this->delMachineServiceLog(['id' => $id]);
        return $rD ? $this->rD($result) : $result;
    }

    /**
     * 按日期向设备下发日志回传指令
     */
    public function getMachineServiceLogByDate($postData)
    {
        $mId = $postData['m_id'] ?? 0;
        $time = $postData['date'] ? strtotime($postData['date']) : time();
        $date = date('Y-m-d', $time);
        if ($mId <= 0) {
            return $this->rFail('设备不存在');
        }
        $machineId = $this->getMachineValue(['m_id' => $mId], 'machine_id');
        if (!$machineId) {
            return $this->rFail('设备不存在');
        }

        $machine = [
            'm_id' => $mId,
            'machine_id' => $machineId,
        ];

        $where = [
            'm_id' => $machine['m_id'],
            'date' => $date,
        ];
        $existList = $this->getMachineServiceLogFind($where, '*', 'id desc');
        //半个小时只能下发一次，避免重复下发
        if (!empty($existList['create_time']) && $existList['create_time'] > (time() - 1800)) {
            return returnState(200, '正在从设备获取运行日志，请稍后刷新列表', [
                'machine_id' => $machine['machine_id'],
                'm_id' => $machine['m_id'],
                'date' => $date,
            ]);
        }

        $result = $this->sendToMachine($machine, 'machineServiceLog', [
            'date' => $date,
        ]);

        if (is_object($result)) {
            return returnState(200, '正在从设备获取运行日志，请稍后刷新列表', [
                'machine_id' => $machine['machine_id'],
                'm_id' => $machine['m_id'],
                'date' => $date,
            ]);
        }

        if (is_string($result)) {
            return $this->rFail($result);
        }

        return $result ?: $this->rFail('下发失败');
    }
}
