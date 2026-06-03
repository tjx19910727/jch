<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/3
 * Time: 14:00
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineAppSettingsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineAppSettingsClient extends ManagementClient
{
    use MachineTrait;
    use MachineAppSettingsTrait;

    protected $settingType = 1;

    public function getSettingsList($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $rows = $this->getMachineAppSettingsList(
            ['m_id' => $machine['m_id'], 'type' => $this->settingType],
            0,
            'id,name,`key`,`value`,value_type,`desc`,updated_at',
            'id asc'
        );
        $rows = obj2arr($rows);
        $rowMap = [];
        foreach ($rows as $row) {
            $rowMap[$row['key']] = $row;
        }

        $result = [];
        foreach ($fieldMap as $key => $meta) {
            $row = $rowMap[$key] ?? [];
            $rawValue = $row['value'] ?? $meta['default'];
            $valueType = $row['value_type'] ?? $meta['value_type'];
            $result[] = [
                'id' => $row['id'] ?? 0,
                'title' => $row['name'] ?? $meta['name'],
                'key' => $key,
                'value' => $this->castValueByType($rawValue, $valueType),
                'value_type' => $valueType,
                'desc' => $row['desc'] ?? $meta['desc'],
                'updated_at' => $row['updated_at'] ?? '',
            ];
        }

        return $this->rQ($result);
    }

    public function getSettingsFind($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $where = [
            'm_id' => $machine['m_id'],
            'type' => $this->settingType,
        ];
        if (!empty($postData['id'])) {
            $where['id'] = $postData['id'];
        } else {
            $key = $postData['key'] ?? '';
            if (!$key) {
                return $this->rFail('key不能为空');
            }
            $where['key'] = $key;
        }

        $row = $this->getMachineAppSettingsFind($where, 'id,name,`key`,`value`,value_type,`desc`,updated_at');
        if (!$row) {
            return $this->rQ([]);
        }

        return $this->rQ([
            'id' => $row['id'],
            'title' => $row['name'],
            'key' => $row['key'],
            'value' => $this->castValueByType($row['value'], $row['value_type'] ?: 'string'),
            'value_type' => $row['value_type'] ?: 'string',
            'desc' => $row['desc'] ?? '',
            'updated_at' => $row['updated_at'] ?? '',
        ]);
    }

    public function updateSettings($postData)
    {
        if (isset($postData['data']) && is_array($postData['data'])) {
            return $this->updateSettingsBatch($postData);
        }
        return $this->updateSettingsSingle($postData);
    }

    protected function updateSettingsSingle($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $row = $this->findSettingRow($machine['m_id'], $postData);
        if (!$row) {
            return $this->rFail('配置不存在，不支持新增');
        }

        if (!array_key_exists('value', $postData)) {
            return $this->rFail('value不能为空');
        }

        $key = $row['key'];
        $check = $this->validateSettingValue($key, $postData['value']);
        if ($check !== true) {
            return $this->rFail($check);
        }

        $title = $fieldMap[$key]['name'] ?? $row['name'];
        $desc = $fieldMap[$key]['desc'] ?? ($row['desc'] ?? '');
        $valueType = $fieldMap[$key]['value_type'] ?? ($row['value_type'] ?: 'string');
        $value = $this->normalizeValueForStorage($postData['value'], $valueType);

        $result = $this->updateMachineAppSettings(
            [
                'name' => $title,
                'value' => $value,
                'value_type' => $valueType,
                'desc' => $desc,
            ],
            ['id' => $row['id']],
            ['name', 'value', 'value_type', 'desc', 'manager_id']
        );

        $this->pushSettingsUpdateMq($machine, [$key]);
        return $this->rA($result);
    }

    protected function updateSettingsBatch($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $list = $postData['data'] ?? [];
        if (!$list) {
            return $this->rFail('data不能为空');
        }

        $updateRows = [];
        $changedKeys = [];
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!array_key_exists('value', $item)) {
                return $this->rFail('value不能为空');
            }
            $row = $this->findSettingRow($machine['m_id'], $item);
            if (!$row) {
                return $this->rFail('存在配置项不存在，不支持新增');
            }
            $key = $row['key'];
            $check = $this->validateSettingValue($key, $item['value']);
            if ($check !== true) {
                return $this->rFail($check);
            }
            $valueType = $fieldMap[$key]['value_type'] ?? ($row['value_type'] ?: 'string');
            $updateRows[$key] = [
                'id' => $row['id'],
                'key' => $key,
                'name' => $fieldMap[$key]['name'] ?? $row['name'],
                'desc' => $fieldMap[$key]['desc'] ?? ($row['desc'] ?? ''),
                'value_type' => $valueType,
                'value' => $this->normalizeValueForStorage($item['value'], $valueType),
            ];
            $changedKeys[$key] = $key;
        }

        if (!$updateRows) {
            return $this->rFail('data不能为空');
        }

        $this->startTrans();
        try {
            foreach ($updateRows as $row) {
                $this->updateMachineAppSettings(
                    [
                        'name' => $row['name'],
                        'value' => $row['value'],
                        'value_type' => $row['value_type'],
                        'desc' => $row['desc'],
                    ],
                    ['id' => $row['id']],
                    ['name', 'value', 'value_type', 'desc', 'manager_id']
                );
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->pushSettingsUpdateMq($machine, array_values($changedKeys));
        return $this->r(200, $this->lang('update_success'));
    }

    protected function findSettingRow($mId, $payload)
    {
        $where = [
            'm_id' => $mId,
            'type' => $this->settingType,
        ];
        if (!empty($payload['id'])) {
            $where['id'] = $payload['id'];
        } else {
            $key = $payload['key'] ?? '';
            if (!$key) {
                return [];
            }
            $where['key'] = $key;
        }
        return $this->getMachineAppSettingsFind($where, 'id,`key`,name,`value`,value_type,`desc`');
    }

    protected function resolveMachine($postData)
    {
        $mId = $postData['m_id'] ?? 0;
        $machineId = $postData['machine_id'] ?? '';

        if (!$mId && $machineId) {
            $mId = $this->getMachineValue(['machine_id' => $machineId], 'm_id');
        }
        if (!$machineId && $mId) {
            $machineId = $this->getMachineValue(['m_id' => $mId], 'machine_id');
        }
        if (!$mId || !$machineId) {
            return [];
        }
        return ['m_id' => $mId, 'machine_id' => $machineId];
    }

    protected function ensureDefaultSettings($machine, $fieldMap)
    {
        foreach ($fieldMap as $key => $meta) {
            $exists = $this->getMachineAppSettingsValue(
                ['m_id' => $machine['m_id'], 'type' => $this->settingType, 'key' => $key],
                'id'
            );
            if ($exists) {
                continue;
            }
            $this->addMachineAppSettings([
                'm_id' => $machine['m_id'],
                'name' => $meta['name'],
                'machine_id' => $machine['machine_id'],
                'type' => $this->settingType,
                'key' => $key,
                'value' => $meta['default'],
                'value_type' => $meta['value_type'],
                'desc' => $meta['desc'],
            ]);
        }
    }

    protected function normalizeValueForStorage($value, $valueType)
    {
        if ($value === null) {
            return '';
        }
        if ($valueType === 'int') {
            return is_numeric($value) ? (string)($value + 0) : (string)$value;
        }
        return (string)$value;
    }

    protected function castValueByType($value, $valueType)
    {
        if ($valueType === 'int') {
            return is_numeric($value) ? $value + 0 : 0;
        }
        return (string)$value;
    }

    protected function validateSettingValue($key, $value)
    {
        $twoStateKeys = [
            'home_anim_enabled',
            'purchase_voice_enabled',
            'dispense_voice_enabled',
            'pickup_voice_enabled',
            'pay_voice_enabled',
            'ad_show_goods_enabled',
        ];
        if (in_array($key, $twoStateKeys, true)) {
            if (!in_array((string)$value, ['1', '2'], true)) {
                return $key . ' 仅支持1或2';
            }
            return true;
        }

        if ($key === 'home_anim_style' || $key === 'ad_goods_jump_target') {
            if (!in_array((string)$value, ['1', '2'], true)) {
                return $key . ' 仅支持1或2';
            }
            return true;
        }

        if ($key === 'home_anim_volume') {
            if (!is_numeric($value)) {
                return 'home_anim_volume 必须为数字';
            }
            $num = $value + 0;
            if ($num < 1 || $num > 100) {
                return 'home_anim_volume 范围为1-100';
            }
            return true;
        }

        return true;
    }

    protected function pushSettingsUpdateMq($machine, $changedKeys)
    {
        $payload = [
            'type' => $this->settingType,
            'event' => 'app_settings_changed',
            'm_id' => $machine['m_id'],
            'machine_id' => $machine['machine_id'],
            'changed_keys' => $changedKeys,
        ];
        $this->sendToMachine($machine, 'appSettingsUpdate', $payload);
    }
}
