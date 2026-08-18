<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

use app\AppFactory\Kernel\Support\FaultNotice\FaultWechatTemplate;
use think\facade\Db;

/**
 * 故障通知总览看板查询复用逻辑。
 */
trait FaultDashboardTrait
{
    /**
     * 顶部指标统计今天并与昨天对比；分布图统计包含今天的近7个自然日。
     *
     * @return array
     */
    public function getFaultOverviewData()
    {
        $period = $this->getFaultDashboardPeriod();
        $now = $period['now'];
        $todayStart = $period['today_start'];
        $yesterdayStart = $period['yesterday_start'];
        $yesterdayEnd = $period['yesterday_end'];
        $sevenDayStart = $period['seven_day_start'];

        $todayFaultCount = $this->countFaultEvents($todayStart, $now);
        $yesterdayFaultCount = $this->countFaultEvents($yesterdayStart, $yesterdayEnd);
        $pendingCount = $this->countCurrentPendingFaults();
        $yesterdayPendingCount = $this->countPendingFaultsAt($yesterdayEnd);
        $todayNoticeCount = $this->countWechatNotices($todayStart, $now);
        $yesterdayNoticeCount = $this->countWechatNotices($yesterdayStart, $yesterdayEnd);
        $todayNoticeSuccessCount = $this->countWechatNotices($todayStart, $now, 1);
        $yesterdayNoticeSuccessCount = $this->countWechatNotices($yesterdayStart, $yesterdayEnd, 1);
        $todaySuccessRate = $this->calculatePercent($todayNoticeSuccessCount, $todayNoticeCount);
        $yesterdaySuccessRate = $this->calculatePercent($yesterdayNoticeSuccessCount, $yesterdayNoticeCount);
        $levels = $this->getFaultLevels();

        return [
            'refresh_time' => date('Y-m-d H:i:s', $now),
            'stat_period' => $this->formatFaultDashboardPeriod($period),
            'metrics' => [
                'today_fault_events' => $this->buildCountMetric($todayFaultCount, $yesterdayFaultCount),
                'pending_events' => $this->buildCountMetric($pendingCount, $yesterdayPendingCount),
                'notice_sent_total' => $this->buildCountMetric($todayNoticeCount, $yesterdayNoticeCount),
                'notice_success_rate' => $this->buildRateMetric($todaySuccessRate, $yesterdaySuccessRate),
            ],
            'level_distribution' => $this->getLevelDistribution($sevenDayStart, $now, $levels),
        ];
    }

    /**
     * 近7日故障等级趋势，供曲线图独立加载。
     *
     * @return array
     */
    public function getFaultTrendData($level = 0)
    {
        $level = $this->normalizeFaultDashboardLevel($level);
        $period = $this->getFaultDashboardPeriod();
        $levels = $this->filterFaultDashboardLevels($this->getFaultLevels(), $level);
        $trend = $this->getLevelTrend(
            $period['seven_day_start'],
            $period['now'],
            $levels,
            $level
        );

        return [
            'refresh_time' => date('Y-m-d H:i:s', $period['now']),
            'stat_period' => [
                'start' => date('Y-m-d', $period['seven_day_start']),
                'end' => date('Y-m-d', $period['now']),
            ],
            'level' => $level,
            'dates' => $trend['dates'],
            'date_labels' => $trend['date_labels'],
            'series' => $trend['series'],
        ];
    }

    /**
     * 近7日故障排行，默认TOP10；详情页可通过top指定返回数量。
     *
     * @param int $top
     * @return array
     */
    public function getFaultTopRankingData($top = 10, $level = 0)
    {
        $level = $this->normalizeFaultDashboardLevel($level);
        $top = intval($top);
        $top = $top > 0 ? min($top, 100) : 10;
        $period = $this->getFaultDashboardPeriod();
        $items = $this->getTopFaults($period['seven_day_start'], $period['now'], $top, $level);

        return [
            'refresh_time' => date('Y-m-d H:i:s', $period['now']),
            'stat_period' => [
                'start' => date('Y-m-d', $period['seven_day_start']),
                'end' => date('Y-m-d', $period['now']),
            ],
            'top' => $top,
            'level' => $level,
            'returned_count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * 近7日设备故障排行，默认TOP10；支持按故障等级筛选。
     *
     * @param int $top
     * @param int $level 0-全部，1-紧急，2-一般，3-提示
     * @return array
     */
    public function getMachineTopRankingData($top = 10, $level = 0)
    {
        $level = $this->normalizeFaultDashboardLevel($level);
        $top = intval($top);
        $top = $top > 0 ? min($top, 100) : 10;
        $period = $this->getFaultDashboardPeriod();
        $items = $this->getTopMachines($period['seven_day_start'], $period['now'], $top, $level);

        return [
            'refresh_time' => date('Y-m-d H:i:s', $period['now']),
            'stat_period' => [
                'start' => date('Y-m-d', $period['seven_day_start']),
                'end' => date('Y-m-d', $period['now']),
            ],
            'top' => $top,
            'level' => $level,
            'returned_count' => count($items),
            'items' => $items,
        ];
    }

    protected function getFaultDashboardPeriod()
    {
        $now = time();
        $todayStart = strtotime(date('Y-m-d 00:00:00', $now));

        return [
            'now' => $now,
            'today_start' => $todayStart,
            'yesterday_start' => strtotime('-1 day', $todayStart),
            'yesterday_end' => $todayStart - 1,
            'seven_day_start' => strtotime('-6 days', $todayStart),
        ];
    }

    protected function formatFaultDashboardPeriod($period)
    {
        return [
            'today' => date('Y-m-d', $period['now']),
            'yesterday' => date('Y-m-d', $period['yesterday_start']),
            'last_7_days_start' => date('Y-m-d', $period['seven_day_start']),
            'last_7_days_end' => date('Y-m-d', $period['now']),
        ];
    }

    /** @return \think\db\Query */
    protected function faultEventQuery()
    {
        return $this->applyFaultDashboardScope(
            Db::name('machine_error_code')->alias('mec'),
            'mec'
        );
    }

    /** @return \think\db\Query */
    protected function faultWechatLogQuery()
    {
        return $this->applyFaultDashboardScope(
            Db::name('wx_template_log')->alias('wtl')
                ->whereIn('wtl.template_type', FaultWechatTemplate::types()),
            'wtl'
        );
    }

    /**
     * 普通组织只看本组织；子账号还要限制在auth_manager_machine授权设备内。
     * 授权设备为空时必须返回空数据，不能因为没有IN条件而放大权限。
     */
    protected function applyFaultDashboardScope($query, $alias)
    {
        $aoId = intval($this->manager['ao_id'] ?? 0);
        if ($aoId > 1) {
            $query->where($alias . '.ao_id', $aoId);
        }

        if (intval($this->manager['pid'] ?? 0) > 0) {
            $managerId = intval($this->manager['manager_id'] ?? 0);
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $managerId], 'm_id');
            $mIds = array_values(array_unique(array_filter(array_map('intval', (array)$mIds))));
            if (!$mIds) {
                $query->where($alias . '.m_id', -1);
            } else {
                $query->whereIn($alias . '.m_id', $mIds);
            }
        }

        return $query;
    }

    protected function countFaultEvents($startTime, $endTime)
    {
        return intval($this->faultEventQuery()
            ->whereBetween('mec.create_time', [intval($startTime), intval($endTime)])
            ->count());
    }

    protected function countCurrentPendingFaults()
    {
        return intval($this->faultEventQuery()->where('mec.status', 1)->count());
    }

    /**
     * 还原指定时点待处理数：当时已发生，且当时尚未处理。
     */
    protected function countPendingFaultsAt($timestamp)
    {
        $timestamp = intval($timestamp);
        return intval($this->faultEventQuery()
            ->where('mec.create_time', '<=', $timestamp)
            ->where(function ($query) use ($timestamp) {
                $query->where('mec.status', 1)
                    ->whereOr(function ($subQuery) use ($timestamp) {
                        $subQuery->where('mec.status', 2)
                            ->where('mec.handle_time', '>', $timestamp);
                    });
            })
            ->count());
    }

    protected function countWechatNotices($startTime, $endTime, $sendStatus = null)
    {
        $query = $this->faultWechatLogQuery()
            ->whereBetween('wtl.create_time', [intval($startTime), intval($endTime)]);
        if ($sendStatus !== null) {
            $query->where('wtl.send_status', intval($sendStatus));
        }
        return intval($query->count());
    }

    /**
     * 即使初始化数据缺失，也返回固定三级，避免图表断线。
     */
    protected function getFaultLevels()
    {
        $rows = Db::name('machine_fault_level')
            ->field('level,grade,level_name,level_desc,color')
            ->order('level asc')
            ->select()
            ->toArray();
        $defaults = [
            1 => ['level' => 1, 'grade' => 1, 'level_name' => '紧急', 'level_desc' => '', 'color' => '#F5222D'],
            2 => ['level' => 2, 'grade' => 2, 'level_name' => '一般', 'level_desc' => '', 'color' => '#FA8C16'],
            3 => ['level' => 3, 'grade' => 3, 'level_name' => '提示', 'level_desc' => '', 'color' => '#1677FF'],
        ];

        foreach ($rows as $row) {
            $level = intval($row['level'] ?? 0);
            if (!isset($defaults[$level])) {
                continue;
            }
            $defaults[$level] = [
                'level' => $level,
                'grade' => intval($row['grade'] ?? $level),
                'level_name' => strval($row['level_name'] ?? $defaults[$level]['level_name']),
                'level_desc' => strval($row['level_desc'] ?? ''),
                'color' => strval($row['color'] ?? $defaults[$level]['color']),
            ];
        }
        return array_values($defaults);
    }

    protected function getLevelDistribution($startTime, $endTime, $levels)
    {
        $rows = $this->faultEventQuery()
            ->whereBetween('mec.create_time', [intval($startTime), intval($endTime)])
            ->field('mec.level,COUNT(*) AS fault_count')
            ->group('mec.level')
            ->select()
            ->toArray();
        $countMap = [];
        foreach ($rows as $row) {
            $countMap[intval($row['level'])] = intval($row['fault_count']);
        }
        $total = array_sum($countMap);
        $items = [];
        foreach ($levels as $level) {
            $count = $countMap[intval($level['level'])] ?? 0;
            $items[] = [
                'level' => intval($level['level']),
                'grade' => intval($level['grade']),
                'level_name' => $level['level_name'],
                'color' => $level['color'],
                'count' => $count,
                'percent' => $this->calculatePercent($count, $total),
            ];
        }
        return ['total' => intval($total), 'items' => $items];
    }

    protected function getLevelTrend($startTime, $endTime, $levels, $level = 0)
    {
        $query = $this->faultEventQuery()
            ->whereBetween('mec.create_time', [intval($startTime), intval($endTime)]);
        $this->applyFaultDashboardLevel($query, $level);
        $rows = $query
            ->field("FROM_UNIXTIME(mec.create_time,'%Y-%m-%d') AS stat_date,mec.level,COUNT(*) AS fault_count")
            ->group('stat_date,mec.level')
            ->order('stat_date asc,mec.level asc')
            ->select()
            ->toArray();
        $countMap = [];
        foreach ($rows as $row) {
            $countMap[strval($row['stat_date'])][intval($row['level'])] = intval($row['fault_count']);
        }

        $dates = [];
        $dateLabels = [];
        for ($timestamp = intval($startTime); $timestamp <= intval($endTime); $timestamp += 86400) {
            $dates[] = date('Y-m-d', $timestamp);
            $dateLabels[] = date('m-d', $timestamp);
        }
        $series = [];
        foreach ($levels as $level) {
            $data = [];
            foreach ($dates as $date) {
                $data[] = intval($countMap[$date][intval($level['level'])] ?? 0);
            }
            $series[] = [
                'level' => intval($level['level']),
                'grade' => intval($level['grade']),
                'name' => $level['level_name'],
                'color' => $level['color'],
                'data' => $data,
            ];
        }
        return ['dates' => $dates, 'date_labels' => $dateLabels, 'series' => $series];
    }

    protected function getTopFaults($startTime, $endTime, $top = 10, $level = 0)
    {
        $top = max(1, min(intval($top), 100));
        $query = $this->faultEventQuery()
            ->leftJoin('machine_error_code_notice_rule mecnr', 'mecnr.ao_id = mec.ao_id AND mecnr.error_code = mec.errorCode')
            ->whereBetween('mec.create_time', [intval($startTime), intval($endTime)]);
        $this->applyFaultDashboardLevel($query, $level);
        $rows = $query
            ->field("mec.errorCode AS error_code,COALESCE(NULLIF(MAX(mecnr.error_name),''),NULLIF(MAX(mec.remark),''),mec.errorCode) AS error_name,COUNT(DISTINCT mec.me_id) AS fault_count")
            ->group('mec.errorCode')
            ->order('fault_count desc,mec.errorCode asc')
            ->limit($top)
            ->select()
            ->toArray();
        return array_map(function ($row, $index) {
            return [
                'rank' => $index + 1,
                'error_code' => strval($row['error_code']),
                'error_name' => strval($row['error_name']),
                'count' => intval($row['fault_count']),
            ];
        }, $rows, array_keys($rows));
    }

    protected function getTopMachines($startTime, $endTime, $top = 10, $level = 0)
    {
        $top = max(1, min(intval($top), 100));
        $query = $this->faultEventQuery()
            ->whereBetween('mec.create_time', [intval($startTime), intval($endTime)])
            ->where('mec.m_id', '>', 0);
        $this->applyFaultDashboardLevel($query, $level);
        $rows = $query
            ->field(
                "mec.m_id,MAX(mec.machine_id) AS machine_id," .
                "MAX(mec.machine_name) AS machine_name,COUNT(DISTINCT mec.me_id) AS fault_count"
            )
            ->group('mec.m_id')
            ->order('fault_count desc,machine_id asc,mec.m_id asc')
            ->limit($top)
            ->select()
            ->toArray();

        return array_map(function ($row, $index) {
            return [
                'rank' => $index + 1,
                'm_id' => intval($row['m_id']),
                'machine_id' => strval($row['machine_id'] ?? ''),
                'machine_name' => strval($row['machine_name'] ?? ''),
                'count' => intval($row['fault_count']),
            ];
        }, $rows, array_keys($rows));
    }

    protected function normalizeFaultDashboardLevel($level)
    {
        $level = intval($level);
        if (!in_array($level, [0, 1, 2, 3], true)) {
            throw new \InvalidArgumentException('故障等级参数错误');
        }
        return $level;
    }

    protected function applyFaultDashboardLevel($query, $level)
    {
        $level = intval($level);
        if ($level > 0) {
            $query->where('mec.level', $level);
        }
        return $query;
    }

    protected function filterFaultDashboardLevels($levels, $level)
    {
        if (intval($level) === 0) {
            return $levels;
        }
        return array_values(array_filter($levels, function ($item) use ($level) {
            return intval($item['level'] ?? 0) === intval($level);
        }));
    }

    protected function buildCountMetric($current, $yesterday)
    {
        $current = intval($current);
        $yesterday = intval($yesterday);
        $change = $current - $yesterday;
        return [
            'value' => $current,
            'yesterday_value' => $yesterday,
            'change' => $change,
            'trend' => $this->getMetricTrend($change),
        ];
    }

    protected function buildRateMetric($current, $yesterday)
    {
        $current = round(floatval($current), 1);
        $yesterday = round(floatval($yesterday), 1);
        $change = round($current - $yesterday, 1);
        return [
            'value' => $current,
            'display' => number_format($current, 1) . '%',
            'yesterday_value' => $yesterday,
            'yesterday_display' => number_format($yesterday, 1) . '%',
            'change' => $change,
            'change_display' => ($change > 0 ? '+' : '') . number_format($change, 1) . '%',
            'change_unit' => 'percentage_point',
            'trend' => $this->getMetricTrend($change),
        ];
    }

    protected function calculatePercent($part, $total)
    {
        $total = intval($total);
        return $total > 0 ? round(intval($part) * 100 / $total, 1) : 0.0;
    }

    protected function getMetricTrend($change)
    {
        return $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat');
    }
}
