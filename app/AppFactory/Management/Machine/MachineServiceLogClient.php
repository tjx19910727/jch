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

    public function add($insert, $rA = 1)
    {
        $postData = $insert;
        $machine = $this->resolveMachine($postData);
        if ($machine) {
            $postData['m_id'] = $machine['m_id'];
            $postData['machine_id'] = $machine['machine_id'];
        }
        $result = $this->addMachineServiceLog($postData);
        if ($rA) {
            return $this->rA($result);
        }
        return $result;
    }

    public function update($update, $where = [], $field = [], $rU = 1)
    {
        $postData = $update;
        $machine = $this->resolveMachine($postData);
        if ($machine) {
            $postData['m_id'] = $machine['m_id'];
            $postData['machine_id'] = $machine['machine_id'];
        }
        $result = $this->updateMachineServiceLog($postData, $where, $field);
        if ($rU) {
            return $this->rU($result);
        }
        return $result;
    }

    public function del($where, $rD = 1)
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
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $where = [
            'm_id' => $machine['m_id'],
            'date' => $postData['date'],
        ];
        $existList = $this->getMachineServiceLogList($where, 0, 'id,m_id,machine_id,name,path,date,remark,create_time', 'id desc');
        if ($existList && count($existList) > 0) {
            return returnState(200, '查询成功', obj2arr($existList));
        }

        $result = $this->sendToMachine($machine, 'machineServiceLog', [
            'date' => $postData['date'],
        ]);

        if (is_array($result) && intval($result['state'] ?? 0) == 200) {
            return returnState(200, '正在从设备获取运行日志，请稍后刷新列表', [
                'machine_id' => $machine['machine_id'],
                'm_id' => $machine['m_id'],
                'date' => $postData['date'],
            ]);
        }

        if (is_string($result)) {
            return $this->rFail($result);
        }

        return $result ?: $this->rFail('下发失败');
    }

    protected function resolveMachine($postData)
    {
        $mId = isset($postData['m_id']) ? intval($postData['m_id']) : 0;
        $machineId = trim($postData['machine_id'] ?? '');

        if ($mId <= 0 && $machineId) {
            $mId = intval($this->getMachineValue(['machine_id' => $machineId], 'm_id'));
        }
        if (!$machineId && $mId > 0) {
            $machineId = (string)$this->getMachineValue(['m_id' => $mId], 'machine_id');
        }
        if ($mId <= 0 || !$machineId) {
            return [];
        }

        return [
            'm_id' => $mId,
            'machine_id' => $machineId,
        ];
    }
}
