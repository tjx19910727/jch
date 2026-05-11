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
        $time = !empty($postData['date']) ? strtotime($postData['date']) : time();
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

        // 已有最近上传结果则直接返回，避免重复等待。
        $existList = $this->getMachineServiceLogFind($where, '*', 'id desc');
        if (!empty($existList['create_time']) && $existList['create_time'] > (time() - 1800) && !empty($existList['path'])) {
            return returnState(200, lang('query_success'), $existList['path']);
        }

        $sendResult = $this->sendToMachine($machine, 'machineServiceLog', [
            'date' => $date,
        ]);

        if (is_string($sendResult)) {
            return $this->rFail($sendResult);
        }

        if (is_array($sendResult) && isset($sendResult['state']) && $sendResult['state'] != 200) {
            return $sendResult;
        }
        $insert = [
            'm_id' => $mId,
            'machine_id' => $machineId,
            'name' => '',
            'path' => '',
            'date' => $date,
            'remark' => '',
            'create_time' => time(),
        ];
        $this->addMachineServiceLog($insert);
        return returnState(200, '下发成功，请稍后到列表查看', []);
        // $startTime = time();
        // $overtime = 50;
        // $n = 0;

        // while (1) {
        //     $waitWhere = [
        //         'm_id' => $machine['m_id'],
        //         'date' => $date,
        //         ['create_time', '>=', $startTime],
        //     ];

        //     $uploadLog = $this->getMachineServiceLogFind($waitWhere, '*', 'id desc');
        //     if (!empty($uploadLog['path'])) {
        //         return returnState(200, lang('query_success'), $uploadLog['path']);
        //     }

        //     sleep(1);
        //     $n++;
        //     if ($n >= $overtime) {
        //         return returnState(300, lang('action_machine_overtime'));
        //     }
        // }
    }
}
