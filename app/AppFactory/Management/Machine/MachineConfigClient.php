<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineCalibrationConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachineConfig;

class MachineConfigClient extends ManagementClient
{
    use MachineTrait,MachineConfigTrait,MachineCalibrationConfigTrait;
    use AuthManagerTrait;

    public function updateMc($postData)
    {
        $result = $this->updateMachineConfig($postData);
        if ($result) {
            $mc = $this->getMachineConfigFind(['mc_id' => $postData['mc_id']],'machine_id');
            $mc = $mc->toArray();
            $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMachineConfig');
        }
        return $this->rU($result);
    }

    public function updateMcV2($postData)
    {
        if (intval($postData['is_only_octopus'] ?? 0) === 1 && (string)($postData['pay_type'] ?? '') !== '8') {
            return $this->r(100, '开启仅支持八达通后，支付类型只能选择CoGoLink(八达通POS机)支付');
        }
        $oldMc = $this->getMachineConfigFind(['mc_id' => $postData['mc_id']], 'mc_id,m_id,machine_id,remote_calibration');
        $oldMc = $oldMc ? $oldMc->toArray() : [];
        $oldRemoteCalibration = isset($oldMc['remote_calibration']) ? intval($oldMc['remote_calibration']) : null;
        $newRemoteCalibration = isset($postData['remote_calibration']) ? intval($postData['remote_calibration']) : null;

        $result = $this->updateMachineConfig($postData);
        if ($result) {
            $machineId = $oldMc['machine_id'] ?? '';
            if (!$machineId) {
                $mc = $this->getMachineConfigFind(['mc_id' => $postData['mc_id']], 'machine_id');
                $mc = $mc ? $mc->toArray() : [];
                $machineId = $mc['machine_id'] ?? '';
            }

            if ($oldRemoteCalibration !== null && $newRemoteCalibration !== null && $oldRemoteCalibration !== $newRemoteCalibration) {
                if ($oldRemoteCalibration === 1 && $newRemoteCalibration === 2) {
                    $mId = intval($oldMc['m_id'] ?? 0);
                    if ($mId > 0) {
                        $this->delMachineCalibrationConfig(['m_id' => $mId]);
                    }
                }

                if ($oldRemoteCalibration === 2 && $newRemoteCalibration === 1 && $machineId) {
                    $this->sendToMachine(['machine_id' => $machineId], 'calibrationAdd');
                }
            }

            if ($machineId) {
                $this->sendToMachine(['machine_id' => $machineId], 'updateMachineConfig');
            }
        }
        return $this->rU($result);
    }

    public function updateMoreMc($postData)
    {
//        $this->startTrans();
        try {
            foreach ($postData['mcList'] as $key => $value) {
                validate(VMachineConfig::class)->scene("mcList")->check($value);
                $result = $this->updateMachineConfig($value, ['m_id' => $value['m_id']]);
                if ($result) {
                    $mc = $this->getMachineConfigFind(['m_id' => $value['m_id']], "machine_id");
                    $mc = $mc->toArray();
                    $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMachineConfig');
                } else {
//                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("update_fail"), $value);
                }
            }
//            $this->commitTrans();
            return $this->r(200, $this->lang("update_success"));
        } catch (\Exception $e) {
//            $this->rollbackTrans();
            actionException($e,1);
            return $this->r(100,$this->lang($e->getMessage()));
        }
    }

}
