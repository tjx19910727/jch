<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

use app\AppFactory\Kernel\Support\FaultNotice\FaultNoticeConfig;
use think\facade\Db;

/**
 * 故障通知全局配置、等级通知策略和配置操作日志。
 */
trait FaultSettingTrait
{
    /** @return array */
    public function getFaultGlobalSettingData()
    {
        $aoId = $this->getFaultSettingAoId();
        $row = Db::name('machine_fault_notice_config')
            ->where('ao_id', $aoId)
            ->find();

        return $this->formatFaultGlobalSetting($row ?: [], (bool)$row);
    }

    /** @return array */
    public function saveFaultGlobalSettingData($params)
    {
        $aoId = $this->getFaultSettingAoId();
        $managerId = intval($this->manager['manager_id'] ?? 0);
        $now = time();
        if (!array_key_exists('notice_enabled', $params)) {
            throw new \InvalidArgumentException('通知总开关不能为空');
        }
        if (!array_key_exists('offline_notice_enabled', $params)) {
            throw new \InvalidArgumentException('离线故障通知开关不能为空');
        }
        $noticeEnabled = intval($params['notice_enabled'] ?? 1);
        $offlineNoticeEnabled = intval($params['offline_notice_enabled'] ?? 1);
        if (!in_array($noticeEnabled, [1, 2], true)) {
            throw new \InvalidArgumentException('通知总开关参数错误');
        }
        if (!in_array($offlineNoticeEnabled, [1, 2], true)) {
            throw new \InvalidArgumentException('离线故障通知开关参数错误');
        }

        $offlineMinutes = intval($params['offline_minutes'] ?? 30);
        if ($offlineMinutes <= 0) {
            $offlineMinutes = 30;
        }

        $data = [
            'notice_enabled' => $noticeEnabled,
            'offline_notice_enabled' => $offlineNoticeEnabled,
            'offline_minutes' => $offlineMinutes,
            'update_id' => $managerId,
            'update_time' => $now,
        ];
        $exists = Db::name('machine_fault_notice_config')
            ->where('ao_id', $aoId)
            ->find();
        if ($exists) {
            Db::name('machine_fault_notice_config')->where('ao_id', $aoId)->update($data);
        } else {
            $data['ao_id'] = $aoId;
            $data['creator'] = $managerId;
            $data['create_time'] = $now;
            Db::name('machine_fault_notice_config')->insert($data);
        }

        return $this->getFaultGlobalSettingData();
    }

    /**
     * 只返回已保存策略；available_levels用于新增下拉框，并携带系统默认建议值。
     *
     * @return array
     */
    public function getFaultLevelStrategyListData()
    {
        $aoId = $this->getFaultSettingAoId();
        $levels = $this->getFaultSettingLevels();
        $defaults = $this->getFaultLevelStrategyDefaults();
        $rows = Db::name('machine_fault_notice_frequency')
            ->where('ao_id', $aoId)
            ->order('level asc')
            ->select()
            ->toArray();

        $levelMap = [];
        foreach ($levels as $level) {
            $levelMap[intval($level['level'])] = $level;
        }
        $configuredLevels = [];
        $items = [];
        foreach ($rows as $row) {
            $level = intval($row['level'] ?? 0);
            if (!isset($levelMap[$level])) {
                continue;
            }
            $configuredLevels[$level] = true;
            $items[] = $this->formatFaultLevelStrategy($row, $levelMap[$level], true);
        }

        $availableLevels = [];
        foreach ($levels as $levelRow) {
            $level = intval($levelRow['level']);
            if (isset($configuredLevels[$level])) {
                continue;
            }
            $availableLevels[] = $this->formatFaultLevelStrategy(
                $defaults[$level],
                $levelRow,
                false
            );
        }

        return [
            'items' => $items,
            'available_levels' => $availableLevels,
            'configured_count' => count($items),
            'max_count' => count($levels),
        ];
    }

    /** @return array */
    public function addFaultLevelStrategyData($params)
    {
        $aoId = $this->getFaultSettingAoId();
        $level = intval($params['level'] ?? 0);
        $levelRow = $this->getFaultSettingLevel($level);
        if (!$levelRow) {
            throw new \InvalidArgumentException('故障等级参数错误');
        }
        if (Db::name('machine_fault_notice_frequency')->where([
            'ao_id' => $aoId,
            'level' => $level,
        ])->find()) {
            throw new \InvalidArgumentException('该故障等级的通知策略已存在');
        }

        $strategy = $this->normalizeFaultLevelStrategy($params, $level);
        $managerId = intval($this->manager['manager_id'] ?? 0);
        $now = time();
        $insert = array_merge($strategy, [
            'ao_id' => $aoId,
            'level' => $level,
            'creator' => $managerId,
            'create_time' => $now,
            'update_id' => $managerId,
            'update_time' => $now,
        ]);
        Db::name('machine_fault_notice_frequency')->insert($insert);

        return $this->formatFaultLevelStrategy($insert, $levelRow, true);
    }

    /** @return array */
    public function updateFaultLevelStrategyData($params)
    {
        $aoId = $this->getFaultSettingAoId();
        $level = intval($params['level'] ?? 0);
        $levelRow = $this->getFaultSettingLevel($level);
        if (!$levelRow) {
            throw new \InvalidArgumentException('故障等级参数错误');
        }
        $exists = Db::name('machine_fault_notice_frequency')->where([
            'ao_id' => $aoId,
            'level' => $level,
        ])->find();
        if (!$exists) {
            throw new \InvalidArgumentException('该故障等级的通知策略不存在');
        }

        $strategy = $this->normalizeFaultLevelStrategy(array_merge($exists, $params), $level);
        $update = array_merge($strategy, [
            'update_id' => intval($this->manager['manager_id'] ?? 0),
            'update_time' => time(),
        ]);
        Db::name('machine_fault_notice_frequency')->where([
            'ao_id' => $aoId,
            'level' => $level,
        ])->update($update);

        return $this->formatFaultLevelStrategy(array_merge($exists, $update), $levelRow, true);
    }

    /** @return array */
    public function deleteFaultLevelStrategyData($level)
    {
        $aoId = $this->getFaultSettingAoId();
        $level = intval($level);
        $levelRow = $this->getFaultSettingLevel($level);
        if (!$levelRow) {
            throw new \InvalidArgumentException('故障等级参数错误');
        }
        $deleted = Db::name('machine_fault_notice_frequency')->where([
            'ao_id' => $aoId,
            'level' => $level,
        ])->delete();
        if (!$deleted) {
            throw new \InvalidArgumentException('该故障等级的通知策略不存在');
        }

        $defaults = $this->getFaultLevelStrategyDefaults();
        return [
            'level' => $level,
            'level_name' => $levelRow['level_name'],
            'default_strategy' => $this->formatFaultLevelStrategy(
                $defaults[$level],
                $levelRow,
                false
            ),
        ];
    }

    /** @return mixed */
    public function getFaultSettingOperationLogData($params, $pageNum = 20)
    {
        $pageNum = max(1, min(intval($pageNum), 100));
        list($startTime, $endTime) = $this->parseFaultSettingLogPeriod($params);
        $pathMap = $this->getFaultSettingLogPathMap();
        $paths = array_keys($pathMap);
        $settingType = strval($params['setting_type'] ?? '');
        if ($settingType !== '') {
            if (!in_array($settingType, ['global', 'level_strategy'], true)) {
                throw new \InvalidArgumentException('设置类型参数错误');
            }
            $paths = array_keys(array_filter($pathMap, function ($item) use ($settingType) {
                return $item['setting_type'] === $settingType;
            }));
        }

        $paginator = Db::name('auth_manager_log')
            ->alias('aml')
            ->where('aml.ao_id', $this->getFaultSettingAoId())
            ->whereIn('aml.path', $paths)
            ->whereBetween('aml.create_time', [$startTime, $endTime])
            ->field('aml.ml_id,aml.manager_id,aml.nickname,aml.account,aml.path,aml.params,aml.create_time')
            ->order('aml.create_time desc,aml.ml_id desc')
            ->paginate($pageNum, false, ['query' => request()->param()]);

        return $paginator->each(function ($row) use ($pathMap) {
            return $this->formatFaultSettingOperationLog($row, $pathMap);
        });
    }

    protected function getFaultSettingAoId()
    {
        return intval($this->manager['ao_id'] ?? 0);
    }

    protected function formatFaultGlobalSetting($row, $configured)
    {
        return [
            'notice_enabled' => intval($row['notice_enabled'] ?? 1),
            'offline_notice_enabled' => intval($row['offline_notice_enabled'] ?? 1),
            'offline_minutes' => intval($row['offline_minutes'] ?? 0) > 0
                ? intval($row['offline_minutes'])
                : 30,
            'is_configured' => $configured ? 1 : 2,
            'update_time' => intval($row['update_time'] ?? 0),
            'update_time_text' => !empty($row['update_time'])
                ? date('Y-m-d H:i:s', intval($row['update_time']))
                : '',
        ];
    }

    protected function getFaultSettingLevels()
    {
        $defaults = [
            1 => ['level' => 1, 'grade' => 1, 'level_name' => '紧急', 'color' => '#F5222D'],
            2 => ['level' => 2, 'grade' => 2, 'level_name' => '一般', 'color' => '#FA8C16'],
            3 => ['level' => 3, 'grade' => 3, 'level_name' => '提示', 'color' => '#1677FF'],
        ];
        $rows = Db::name('machine_fault_level')
            ->field('level,grade,level_name,color')
            ->whereIn('level', [1, 2, 3])
            ->order('level asc')
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $level = intval($row['level'] ?? 0);
            if (!isset($defaults[$level])) {
                continue;
            }
            $defaults[$level] = [
                'level' => $level,
                'grade' => intval($row['grade'] ?? $level),
                'level_name' => strval($row['level_name'] ?? $defaults[$level]['level_name']),
                'color' => strval($row['color'] ?? $defaults[$level]['color']),
            ];
        }
        return array_values($defaults);
    }

    protected function getFaultSettingLevel($level)
    {
        foreach ($this->getFaultSettingLevels() as $row) {
            if (intval($row['level']) === intval($level)) {
                return $row;
            }
        }
        return [];
    }

    protected function getFaultLevelStrategyDefaults()
    {
        return FaultNoticeConfig::levelStrategyDefaults();
    }

    protected function normalizeFaultLevelStrategy($params, $level)
    {
        $defaults = $this->getFaultLevelStrategyDefaults();
        $default = $defaults[$level];
        $quietEnabled = intval($params['quiet_enabled'] ?? $default['quiet_enabled']);
        if (!in_array($quietEnabled, [1, 2], true)) {
            throw new \InvalidArgumentException('静默时段开关参数错误');
        }
        if ($level === 1 && $quietEnabled === 1) {
            throw new \InvalidArgumentException('紧急等级不允许设置静默时间');
        }

        $quietStart = null;
        $quietEnd = null;
        if ($quietEnabled === 1) {
            $quietStart = $this->normalizeFaultSettingTime($params['quiet_start'] ?? '');
            $quietEnd = $this->normalizeFaultSettingTime($params['quiet_end'] ?? '');
            if (!$quietStart || !$quietEnd) {
                throw new \InvalidArgumentException('启用静默时段时，开始时间和结束时间不能为空');
            }
            if ($quietStart === $quietEnd) {
                throw new \InvalidArgumentException('静默开始时间和结束时间不能相同');
            }
        }

        $intervalMinutes = intval($params['interval_minutes'] ?? $default['interval_minutes']);
        $dayLimit = intval($params['day_limit'] ?? $default['day_limit']);
        if ($intervalMinutes < 0) {
            throw new \InvalidArgumentException('通知间隔不能小于0分钟');
        }
        if ($dayLimit <= 0) {
            throw new \InvalidArgumentException('每日通知上限必须大于0');
        }

        return [
            'quiet_enabled' => $quietEnabled,
            'quiet_start' => $quietStart,
            'quiet_end' => $quietEnd,
            'interval_minutes' => $intervalMinutes,
            'day_limit' => $dayLimit,
        ];
    }

    protected function normalizeFaultSettingTime($time)
    {
        $time = trim(strval($time));
        if (!preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d(?::[0-5]\\d)?$/', $time)) {
            return null;
        }
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    protected function formatFaultLevelStrategy($row, $levelRow, $configured)
    {
        return [
            'level' => intval($levelRow['level']),
            'grade' => intval($levelRow['grade']),
            'level_name' => strval($levelRow['level_name']),
            'color' => strval($levelRow['color']),
            'quiet_enabled' => intval($row['quiet_enabled'] ?? 2),
            'quiet_start' => $this->formatFaultSettingTime($row['quiet_start'] ?? null),
            'quiet_end' => $this->formatFaultSettingTime($row['quiet_end'] ?? null),
            'interval_minutes' => intval($row['interval_minutes'] ?? 0),
            'day_limit' => intval($row['day_limit'] ?? 1),
            'is_configured' => $configured ? 1 : 2,
            'update_time' => intval($row['update_time'] ?? 0),
            'update_time_text' => !empty($row['update_time'])
                ? date('Y-m-d H:i:s', intval($row['update_time']))
                : '',
        ];
    }

    protected function formatFaultSettingTime($time)
    {
        if (!$time) {
            return '';
        }
        return substr(strval($time), 0, 5);
    }

    protected function parseFaultSettingLogPeriod($params)
    {
        $start = null;
        $end = null;
        $range = $params['create_time'] ?? null;
        if (is_array($range)) {
            $start = $range[0] ?? null;
            $end = $range[1] ?? null;
        } elseif (is_string($range) && strpos($range, '~') !== false) {
            list($start, $end) = array_map('trim', explode('~', $range, 2));
        }
        $start = $params['start_date'] ?? ($params['start_time'] ?? $start);
        $end = $params['end_date'] ?? ($params['end_time'] ?? $end);

        if ($start === null || $start === '') {
            $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days')));
        } else {
            $startTime = is_numeric($start) ? intval($start) : strtotime(strval($start) . ' 00:00:00');
        }
        if ($end === null || $end === '') {
            $endTime = time();
        } else {
            $endTime = is_numeric($end) ? intval($end) : strtotime(strval($end) . ' 23:59:59');
        }
        if (!$startTime || !$endTime || $startTime > $endTime) {
            throw new \InvalidArgumentException('日志查询日期范围错误');
        }
        return [$startTime, $endTime];
    }

    protected function getFaultSettingLogPathMap()
    {
        return [
            '/management/fault_notice.fault_setting/saveGlobal' => [
                'setting_type' => 'global',
                'setting_type_name' => '全局设置',
                'action' => 'save',
                'action_name' => '保存',
            ],
            '/management/fault_notice.fault_setting/addLevelStrategy' => [
                'setting_type' => 'level_strategy',
                'setting_type_name' => '等级通知策略',
                'action' => 'add',
                'action_name' => '新增',
            ],
            '/management/fault_notice.fault_setting/updateLevelStrategy' => [
                'setting_type' => 'level_strategy',
                'setting_type_name' => '等级通知策略',
                'action' => 'update',
                'action_name' => '修改',
            ],
            '/management/fault_notice.fault_setting/deleteLevelStrategy' => [
                'setting_type' => 'level_strategy',
                'setting_type_name' => '等级通知策略',
                'action' => 'delete',
                'action_name' => '删除',
            ],
        ];
    }

    protected function formatFaultSettingOperationLog($row, $pathMap)
    {
        $path = strval($row['path'] ?? '');
        $meta = $pathMap[$path] ?? [
            'setting_type' => '',
            'setting_type_name' => '',
            'action' => '',
            'action_name' => '',
        ];
        $params = json_decode(strval($row['params'] ?? ''), true);
        if (!is_array($params)) {
            $params = [];
        }
        return [
            'log_id' => intval($row['ml_id'] ?? 0),
            'manager_id' => intval($row['manager_id'] ?? 0),
            'nickname' => strval($row['nickname'] ?? ''),
            'account' => strval($row['account'] ?? ''),
            'setting_type' => $meta['setting_type'],
            'setting_type_name' => $meta['setting_type_name'],
            'action' => $meta['action'],
            'action_name' => $meta['action_name'],
            'content' => $this->buildFaultSettingLogContent($meta, $params),
            'params' => $params,
            'create_time' => intval($row['create_time'] ?? 0),
            'create_time_text' => !empty($row['create_time'])
                ? date('Y-m-d H:i:s', intval($row['create_time']))
                : '',
        ];
    }

    protected function buildFaultSettingLogContent($meta, $params)
    {
        if ($meta['setting_type'] === 'global') {
            return sprintf(
                '保存全局设置：总开关%s，离线通知%s，离线阈值%d分钟',
                intval($params['notice_enabled'] ?? 1) === 1 ? '开启' : '关闭',
                intval($params['offline_notice_enabled'] ?? 1) === 1 ? '开启' : '关闭',
                intval($params['offline_minutes'] ?? 0) > 0
                    ? intval($params['offline_minutes'])
                    : 30
            );
        }
        $level = intval($params['level'] ?? 0);
        $names = [1 => '紧急', 2 => '一般', 3 => '提示'];
        return sprintf('%s%s等级通知策略', $meta['action_name'], $names[$level] ?? ('等级' . $level));
    }
}
