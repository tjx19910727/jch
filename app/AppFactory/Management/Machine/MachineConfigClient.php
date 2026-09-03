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
use app\AppFactory\Kernel\Service\Currency\MachineCurrencyAccessService;
use app\AppFactory\Kernel\Service\Currency\MachineCurrencySwitchService;

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
        if (array_key_exists('currency_code', $postData) || array_key_exists('currency_version', $postData) || array_key_exists('currency_name', $postData) || array_key_exists('currency_symbol', $postData)) {
            return $this->r(100, '设备币种只能通过币种切换接口修改');
        }
        $oldMc = $this->getMachineConfigFind(['mc_id' => $postData['mc_id']], 'mc_id,m_id,machine_id,remote_calibration,is_multi_goods');
        $oldMc = $oldMc ? $oldMc->toArray() : [];
        $oldRemoteCalibration = isset($oldMc['remote_calibration']) ? intval($oldMc['remote_calibration']) : null;
        $newRemoteCalibration = isset($postData['remote_calibration']) ? intval($postData['remote_calibration']) : null;
        $oldIsMultiGoods = intval($oldMc['is_multi_goods'] ?? 2);
        $newIsMultiGoods = array_key_exists('is_multi_goods', $postData) ? intval($postData['is_multi_goods']) : null;

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
                // ==================== 单货道多商品相关开始 ====================
                $closedChannels = [];
                if ($oldIsMultiGoods === 1 && $newIsMultiGoods === 2 && !empty($oldMc['m_id'])) {
                    $closedChannels = $this->closeMachineMultiGoods($oldMc['m_id']);
                    if($closedChannels){
                        $this->sendClosedMultiGoodsChannelUpdates($machineId, $closedChannels);
                    }
                }
                // ==================== 单货道多商品相关结束 ====================
            }
        }
        return $this->rU($result);
    }

    public function updateMoreMc($postData)
    {
//        $this->startTrans();
        try {
            foreach ($postData['mcList'] as $key => $value) {
                if (array_key_exists('currency_code', $value) || array_key_exists('currency_version', $value) || array_key_exists('currency_name', $value) || array_key_exists('currency_symbol', $value)) {
                    return $this->r(100, '设备币种只能通过币种切换接口修改');
                }
                validate(VMachineConfig::class)->scene("mcList")->check($value);
                $oldMc = $this->getMachineConfigFind(
                    ['m_id' => $value['m_id']],
                    'machine_id,is_multi_goods'
                );
                $oldMc = $oldMc ? $oldMc->toArray() : [];
                $oldIsMultiGoods = intval($oldMc['is_multi_goods'] ?? 2);
                $newIsMultiGoods = array_key_exists('is_multi_goods', $value)
                    ? intval($value['is_multi_goods'])
                    : null;

                $result = $this->updateMachineConfig($value, ['m_id' => $value['m_id']]);
                if ($result) {
                    $machineId = $oldMc['machine_id'] ?? '';
                    if (!$machineId) {
                        $mc = $this->getMachineConfigFind(['m_id' => $value['m_id']], 'machine_id');
                        $mc = $mc ? $mc->toArray() : [];
                        $machineId = $mc['machine_id'] ?? '';
                    }
                    if ($machineId) {
                        $this->sendToMachine(['machine_id' => $machineId], 'updateMachineConfig');
                    }
                    // ==================== 单货道多商品相关开始 ====================
                    $closedChannels = [];
                    if ($oldIsMultiGoods === 1 && $newIsMultiGoods === 2) {
                        $closedChannels = $this->closeMachineMultiGoods($value['m_id']);
                        if ($closedChannels && $machineId) {
                            $this->sendClosedMultiGoodsChannelUpdates($machineId, $closedChannels);
                        }
                    }
                    // ==================== 单货道多商品相关结束 ====================
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

    public function currencyReadiness($postData)
    {
        try {
            $mId = intval(isset($postData['m_id']) ? $postData['m_id'] : 0);
            (new MachineCurrencyAccessService())->assertManagementAccess($mId, $this->manager);
            $result = (new MachineCurrencySwitchService())->readiness($mId, $postData['currency_code']);
            return $this->r(200, $this->lang('query_success'), $result);
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }
    }

    public function switchCurrency($postData)
    {
        try {
            $mId = intval(isset($postData['m_id']) ? $postData['m_id'] : 0);
            (new MachineCurrencyAccessService())->assertManagementAccess($mId, $this->manager);
            $result = (new MachineCurrencySwitchService())->switchCurrency($mId, $postData['currency_code']);
            if (empty($result['success'])) {
                return $this->r(100, '设备币种切换条件未满足', $result['readiness']);
            }
            if (empty($result['idempotent'])) {
                $this->sendToMachine(['machine_id' => $result['machine_id']], 'currencySnapshotUpdated', [
                    'currency_code' => $result['currency_code'],
                    'currency_version' => intval($result['currency_version']),
                ]);
            }
            return $this->r(200, $this->lang('update_success'), $result);
        } catch (\Exception $e) {
            actionException($e, 1, 'switchMachineCurrency');
            return $this->rValidate($e->getMessage());
        }
    }

    public function switchCurrencyBatch($postData)
    {
        $mIds = isset($postData['m_ids']) ? $postData['m_ids'] : [];
        if (is_string($mIds)) $mIds = explode(',', $mIds);
        $mIds = array_values(array_unique(array_filter(array_map('intval', (array)$mIds))));
        if (!$mIds || count($mIds) > 50) return $this->r(100, '请选择1至50台设备');
        // 每台设备独立执行事务，单台失败不回滚已经成功切换的其他设备。
        $result = ['success' => [], 'failed' => []];
        foreach ($mIds as $mId) {
            try {
                (new MachineCurrencyAccessService())->assertManagementAccess($mId, $this->manager);
                $one = (new MachineCurrencySwitchService())->switchCurrency($mId, $postData['currency_code']);
                if (empty($one['success'])) {
                    $result['failed'][] = ['m_id' => $mId, 'readiness' => $one['readiness']];
                    continue;
                }
                $result['success'][] = $one;
                if (empty($one['idempotent'])) {
                    $this->sendToMachine(['machine_id' => $one['machine_id']], 'currencySnapshotUpdated', [
                        'currency_code' => $one['currency_code'],
                        'currency_version' => intval($one['currency_version']),
                    ]);
                }
            } catch (\Exception $e) {
                $result['failed'][] = ['m_id' => $mId, 'message' => $e->getMessage()];
            }
        }
        return $this->r(200, $this->lang('action_success'), $result);
    }

    // ==================== 单货道多商品相关开始 ====================
    private function sendClosedMultiGoodsChannelUpdates($machineId, array $channels)
    {
        foreach ($channels as $channel) {
            if (intval($channel['channel_position'] ?? 1) === 3) {
                continue;
            }
            $this->sendToMachine(
                ['machine_id' => $machineId],
                'updateMc',
                ['mc_id' => $channel['mc_id']]
            );
        }
    }
    // ==================== 单货道多商品相关结束 ====================
}
