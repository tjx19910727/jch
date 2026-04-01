<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/31
 * Time: 11:00
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineCalibrationConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineCalibrationConfigClient extends ManagementClient
{
    use MachineTrait;
    use MachineCalibrationConfigTrait;

    public function getCalibrationList($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $version = $postData['version'] ?? '';
        if (!$version) {
            $version = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'id desc');
        }
        if (!$version) {
            return $this->rQ([]);
        }

        $list = $this->getMachineCalibrationConfigList(
            ['m_id' => $machine['m_id'], 'version' => $version],
            0,
            'id,name,`key`,`value`,value_type',
            'id asc'
        );
        $list = obj2arr($list);

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'title' => $item['name'],
                'key' => $item['key'],
                'value' => $this->castValueByType($item['value'], $item['value_type'] ?? 'string'),
            ];
        }

        return $this->rQ($result);
    }

    public function getCalibrationFind($postData)
    {
        $where = [];
        if (!empty($postData['id'])) {
            $where['id'] = intval($postData['id']);
        } else {
            $machine = $this->resolveMachine($postData);
            if (!$machine) {
                return $this->rFail('设备不存在');
            }
            if (empty($postData['key'])) {
                return $this->rFail('key不能为空');
            }
            $version = $postData['version'] ?? '';
            if (!$version) {
                $version = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'id desc');
            }
            $where = ['m_id' => $machine['m_id'], 'version' => $version, 'key' => $postData['key']];
        }

        $row = $this->getMachineCalibrationConfigFind($where, 'id,name,`key`,`value`,value_type');
        if (!$row) {
            return $this->rQ([]);
        }

        return $this->rQ([
            'title' => $row['name'],
            'key' => $row['key'],
            'value' => $this->castValueByType($row['value'], $row['value_type'] ?? 'string'),
        ]);
    }

    public function addCalibration($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        if (!isset($postData['key']) || $postData['key'] === '') {
            return $this->rFail('key不能为空');
        }

        $version = $postData['version'] ?? '';
        if (!$version) {
            $version = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'id desc');
            if (!$version) {
                $version = '1.0.0';
            }
        }

        $valueType = $this->detectValueType($postData['value'] ?? null, $postData['value_type'] ?? '');
        $value = $this->normalizeValueForStorage($postData['value'] ?? null, $valueType);

        $result = $this->addMachineCalibrationConfig([
            'm_id' => $machine['m_id'],
            'machine_id' => $machine['machine_id'],
            'version' => $version,
            'name' => $postData['title'],
            'key' => $postData['key'],
            'value' => $value,
            'value_type' => $valueType,
            'desc' => $postData['desc'] ?? '',
        ]);

        return $this->rA($result);
    }

    public function updateCalibration($postData)
    {
        if (empty($postData['id'])) {
            return $this->rFail('id不能为空');
        }

        $row = $this->getMachineCalibrationConfigFind(['id' => intval($postData['id'])]);
        if (!$row) {
            return $this->rFail('配置不存在');
        }

        $update = ['id' => intval($postData['id'])];
        if (isset($postData['title'])) {
            $update['name'] = $postData['title'];
        }
        if (isset($postData['key'])) {
            $update['key'] = $postData['key'];
        }
        if (array_key_exists('value', $postData)) {
            $valueType = $this->detectValueType($postData['value'], $postData['value_type'] ?? ($row['value_type'] ?? ''));
            $update['value_type'] = $valueType;
            $update['value'] = $this->normalizeValueForStorage($postData['value'], $valueType);
        }
        if (isset($postData['desc'])) {
            $update['desc'] = $postData['desc'];
        }

        $result = $this->updateMachineCalibrationConfig($update);
        return $this->rU($result);
    }

    public function updateCalibrationList($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $list = $postData['data'] ?? [];
        if (!$list || !is_array($list)) {
            return $this->rFail('data不能为空');
        }

        $latestVersion = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'id desc');
        $newVersion = $latestVersion ? $this->increaseVersion($latestVersion) : ($postData['version'] ?? '1.0.0');

        $this->startTrans();
        try {
            foreach ($list as $item) {
                if (!isset($item['key']) || $item['key'] === '') {
                    continue;
                }
                $valueType = $this->detectValueType($item['value'] ?? null, $item['value_type'] ?? '');
                $value = $this->normalizeValueForStorage($item['value'] ?? null, $valueType);
                $this->addMachineCalibrationConfig([
                    'm_id' => $machine['m_id'],
                    'machine_id' => $machine['machine_id'],
                    'version' => $newVersion,
                    'name' => $item['title'] ?? $item['key'],
                    'key' => $item['key'],
                    'value' => $value,
                    'value_type' => $valueType,
                    'desc' => $item['desc'] ?? '',
                ]);
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->sendToMachine(['machine_id' => $machine['machine_id']], 'updateMachineCalibrationConfig', ['version' => $newVersion]);
        return $this->r(200, $this->lang('update_success'), ['version' => $newVersion]);
    }

    public function delCalibration($postData)
    {
        if (!empty($postData['id'])) {
            return $this->rD($this->delMachineCalibrationConfig(['id' => intval($postData['id'])]));
        }

        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $version = $postData['version'] ?? '';
        if (!$version) {
            $version = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'id desc');
        }

        if (!empty($postData['key'])) {
            $where = ['m_id' => $machine['m_id'], 'version' => $version, 'key' => $postData['key']];
            return $this->rD($this->delMachineCalibrationConfig($where));
        }

        if (!empty($postData['keys']) && is_array($postData['keys'])) {
            $where = [
                ['m_id', '=', $machine['m_id']],
                ['version', '=', $version],
                ['key', 'in', $postData['keys']],
            ];
            return $this->rD($this->delMachineCalibrationConfig($where));
        }

        return $this->rFail('请传id或key');
    }

    protected function resolveMachine($postData)
    {
        $mId = isset($postData['m_id']) ? intval($postData['m_id']) : 0;
        $machineId = $postData['machine_id'] ?? '';

        if ($mId <= 0 && $machineId) {
            $mId = intval($this->getMachineValue(['machine_id' => $machineId], 'm_id'));
        }
        if (!$machineId && $mId > 0) {
            $machineId = (string)$this->getMachineValue(['m_id' => $mId], 'machine_id');
        }
        if ($mId <= 0 || !$machineId) {
            return [];
        }
        return ['m_id' => $mId, 'machine_id' => $machineId];
    }

    protected function detectValueType($value, $inputType = '')
    {
        $inputType = strtolower((string)$inputType);
        if (in_array($inputType, ['string', 'int', 'float', 'bool'], true)) {
            return $inputType;
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        return 'string';
    }

    protected function normalizeValueForStorage($value, $valueType)
    {
        if ($value === null) {
            return '';
        }
        if ($valueType === 'bool') {
            return $value ? '1' : '0';
        }
        return (string)$value;
    }

    protected function castValueByType($value, $valueType)
    {
        switch ((string)$valueType) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }
                $v = strtolower(trim((string)$value));
                return in_array($v, ['1', 'true', 'yes', 'on'], true);
            default:
                return (string)$value;
        }
    }

    protected function increaseVersion($version)
    {
        $parts = explode('.', (string)$version);
        $nums = [];
        foreach ($parts as $part) {
            $nums[] = is_numeric($part) ? intval($part) : 0;
        }
        while (count($nums) < 3) {
            $nums[] = 0;
        }

        $idx = count($nums) - 1;
        $nums[$idx]++;
        while ($idx > 0 && $nums[$idx] > 99) {
            $nums[$idx] = 0;
            $idx--;
            $nums[$idx]++;
        }

        return implode('.', $nums);
    }
}
