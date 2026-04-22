<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/31
 * Time: 11:00
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineCalibrationConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineCalibrationConfigClient extends ManagementClient
{
    use MachineTrait;
    use MachineConfigTrait;
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
                $version = 1;
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

        $machine = $this->resolveMachine(['m_id' => intval($row['m_id'])]);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $latestVersion = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'id desc');
        if (!$latestVersion) {
            return $this->rFail('当前设备暂无配置');
        }
        $newVersion = $this->increaseVersion($latestVersion);

        $latestRows = $this->getMachineCalibrationConfigList(
            ['m_id' => $machine['m_id'], 'version' => $latestVersion],
            0,
            'id,name,`key`,`value`,value_type,`desc`',
            'id asc'
        );
        $latestRows = obj2arr($latestRows);
        if (!$latestRows) {
            return $this->rFail('当前设备暂无配置');
        }

        $targetId = $postData['id'];
        $targetKey = $row['key'];

        $this->startTrans();
        try {
            foreach ($latestRows as $old) {
                $title = $old['name'];
                $key = $old['key'];
                $value = $old['value'];
                $valueType = $old['value_type'] ?: 'string';
                $desc = $old['desc'] ?? '';

                $isTarget = ($old['id'] == $targetId) || ($old['key'] == $targetKey);
                if ($isTarget) {
                    if (isset($postData['title'])) {
                        $title = $postData['title'];
                    }
                    if (isset($postData['key']) && $postData['key'] !== '') {
                        $key = $postData['key'];
                    }
                    if (array_key_exists('value', $postData)) {
                        // 沿用上一版本对应配置项的value_type，保证类型以设备端上报为准
                        $value = $this->normalizeValueForStorage($postData['value'], $valueType);
                    }
                    if (isset($postData['desc'])) {
                        $desc = $postData['desc'];
                    }
                }

                $this->addMachineCalibrationConfig([
                    'm_id' => $machine['m_id'],
                    'machine_id' => $machine['machine_id'],
                    'version' => $newVersion,
                    'name' => $title,
                    'key' => $key,
                    'value' => $value,
                    'value_type' => $valueType,
                    'desc' => $desc,
                ]);
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->pushCalibrationUpdateMq($machine, $newVersion);
        return $this->r(200, $this->lang('update_success'), ['version' => $newVersion]);
    }

    public function updateCalibrationList($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $machineConfig = $this->getMachineConfigFind(['m_id' => $machine['m_id']], 'remote_calibration');
        $remoteCalibration = $machineConfig['remote_calibration'] ?? 0;
        if ($remoteCalibration != 1) {
            return $this->rFail('先开启设备远程校准');
        }

        $list = $postData['data'] ?? [];
        if (!$list || !is_array($list)) {
            return $this->rFail('data不能为空');
        }

        $latestVersion = $this->getMachineCalibrationConfigValue(['m_id' => $machine['m_id']], 'version', 'version desc');
        if ($latestVersion === null || $latestVersion === '') {
            return $this->rFail('当前设备暂无配置');
        }
        $newVersion = $this->increaseVersion($latestVersion);

        $latestRows = $this->getMachineCalibrationConfigList(
            ['m_id' => $machine['m_id'], 'version' => $latestVersion],
            0,
            'name,`key`,`value`,value_type,`desc`',
            'id asc'
        );
        $latestRows = obj2arr($latestRows);
        $latestMap = [];
        foreach ($latestRows as $val) {
            $latestMap[$val['key']] = $val;
        }

        // 同批次按key去重，最后一条生效，避免重复key写入同一新版本。
        $inputMap = [];
        foreach ($list as $item) {
            if (!isset($item['key']) || $item['key'] === '') {
                continue;
            }
            $inputMap[$item['key']] = $item;
        }

        $this->startTrans();
        try {
            foreach ($inputMap as $item) {
                $key = $item['key'];
                $old = $latestMap[$key] ?? [];
                // 沿用上一版本对应配置项的value_type，保证类型以设备端上报为准
                $valueType = $old['value_type'] ?? 'string';
                $value = $this->normalizeValueForStorage($item['value'] ?? null, $valueType);
                $this->addMachineCalibrationConfig([
                    'm_id' => $machine['m_id'],
                    'machine_id' => $machine['machine_id'],
                    'version' => $newVersion,
                    'name' => $item['title'] ?? ($old['name'] ?? $key),
                    'key' => $key,
                    'value' => $value,
                    'value_type' => $valueType,
                    'desc' => $item['desc'] ?? ($old['desc'] ?? ''),
                ]);
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->pushCalibrationUpdateMq($machine, $newVersion);
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
            return $value ? 'true' : 'false';
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
        $v = intval($version);
        if ($v < 0) {
            $v = 0;
        }
        return $v + 1;
    }

    /**
     * 触发设备端校准配置更新消息
     * @param array $machine
     * @param string $version
     */
    protected function pushCalibrationUpdateMq($machine, $version)
    {
        $payload = [
            'data' => $this->buildCalibrationDataByVersion($machine['m_id'], $version),
            'm_id' => intval($machine['m_id']),
            'version' => intval($version),
        ];
        $this->sendToMachine($machine, 'calibrationUpdate', $payload);
    }

    /**
     * 组装指定版本的校准配置数据
     * 格式同设备端 getCalibrationConfig 入参中的 data
     * @param int $mId
     * @param string $version
     * @return array
     */
    protected function buildCalibrationDataByVersion($mId, $version)
    {
        $rows = $this->getMachineCalibrationConfigList(
            ['m_id' => $mId, 'version' => $version],
            0,
            'name,`key`,`value`,value_type',
            'id asc'
        );
        $rows = obj2arr($rows);
        $data = [];
        foreach ($rows as $row) {
            $data[$row['key']] = $this->castValueByType($row['value'], $row['value_type'] ?? 'string');
        }
        $data['version'] = $version;
        return $data;
    }
}
