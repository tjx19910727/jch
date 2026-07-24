<?php

namespace app\management\service;

use app\AppFactory\Management\Application;
use app\AppFactory\RabbitMq\MqProducer;
use think\facade\Db;

class MachineTargetService
{
    /** @var Application */
    protected $app;

    /** @var array */
    protected $manager;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->manager = $app->getConfig();
    }

    /**
     * @param array{m_id:mixed,date:mixed,price:mixed,target_amount?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function add(array $ctx): array
    {
        $mIds = $this->normalizeMachineIdInput($ctx['m_id'] ?? '');
        if ($mIds === []) {
            return ['state' => 100, 'msg' => '设备id不能为空', 'data' => []];
        }
        //传入的m_id是否在getWhere的权限范围内，避免越权操作
        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return ['state' => 100, 'msg' => '当前账号下没有可操作设备', 'data' => []];
        }
        $mIds = array_values(array_intersect($mIds, $allowedMids));
        if ($mIds === []) {
            return ['state' => 100, 'msg' => '设备不在当前账号可操作范围内', 'data' => []];
        }
        // 日期和金额输入解析
        $monthPriceParse = $this->parseMonthPriceInput($ctx['date'] ?? '', $ctx['price'] ?? '');
        if (($monthPriceParse['state'] ?? 100) !== 200) {
            return ['state' => 100, 'msg' => (string) ($monthPriceParse['msg'] ?? '日期或金额格式错误'), 'data' => []];
        }

        $months = is_array($monthPriceParse['months'] ?? null) ? $monthPriceParse['months'] : [];
        $priceList = is_array($monthPriceParse['price_list'] ?? null) ? $monthPriceParse['price_list'] : [];
        $monthPriceMap = is_array($monthPriceParse['month_price_map'] ?? null) ? $monthPriceParse['month_price_map'] : [];
        if ($months === [] || $priceList === [] || $monthPriceMap === []) {
            return ['state' => 100, 'msg' => '日期或金额不能为空', 'data' => []];
        }

        $defaultTargetParse = $this->parseDefaultTargetAmount($ctx['target_amount'] ?? 0);
        if (($defaultTargetParse['state'] ?? 100) !== 200) {
            return ['state' => 100, 'msg' => strval($defaultTargetParse['msg'] ?? '默认目标金额格式错误'), 'data' => []];
        }
        $defaultTargetAmount = round((float) ($defaultTargetParse['amount'] ?? 0), 2);

        $currentMonth = date('Y-m');
        foreach ($months as $month) {
            if (strval($month) < $currentMonth) {
                return ['state' => 100, 'msg' => '设置月份不能小于当前月', 'data' => []];
            }
        }

        sort($mIds);
        sort($months);

        Db::startTrans();
        try {
            Db::name('machine_target_monthly')
                ->whereIn('m_id', $mIds)
                ->where('month', '>=', $currentMonth)
                ->delete();

            $rows = [];
            foreach ($months as $month) {
                $bounds = $this->monthBounds($month);
                $monthPrice = round((float) ($monthPriceMap[$month] ?? 0), 2);
                foreach ($mIds as $mid) {
                    $rows[] = [
                        'target_group_id' => 0,
                        'm_id' => $mid,
                        'month' => $month,
                        'start_time' => $bounds['start'],
                        'end_time' => $bounds['end'],
                        'target_amount' => $monthPrice,
                    ];
                }
            }

            if ($rows !== []) {
                Db::name('machine_target_monthly')->insertAll($rows);
            }

            // 默认目标值与月度目标独立：同设备同月份覆盖，0不新增也不更新。
            if ($defaultTargetAmount > 0) {
                $defaultRows = [];
                $now = time();
                foreach ($mIds as $mid) {
                    $defaultRows[] = [
                        'm_id' => $mid,
                        'months' => $currentMonth,
                        'target_amount' => $defaultTargetAmount,
                        'create_time' => $now,
                    ];
                }
                Db::name('machine_target_group')
                    ->duplicate(['target_amount', 'create_time'])
                    ->insertAll($defaultRows);
            }

            Db::commit();
            return [
                'state' => 200,
                'msg' => '设置成功',
                'data' => [
                    'm_id' => implode(',', $mIds),
                    'm_id_list' => $mIds,
                    'date' => implode(',', $months),
                    'months' => $months,
                    'price' => implode(',', array_map('strval', $priceList)),
                    'price_list' => $priceList,
                    'target_amount' => $defaultTargetAmount,
                    'rows' => count($rows),
                ],
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return ['state' => 100, 'msg' => '设置失败：' . $e->getMessage(), 'data' => []];
        }
    }

    /**
     * @param array{id?:mixed,m_id:mixed,date:mixed,price:mixed,target_amount?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function update(array $ctx): array
    {
        // 月表与默认目标表不再通过group id关联，修改与新增使用相同的按设备覆盖逻辑。
        return $this->add($ctx);
    }

    /**
     * 目标配置列表（用于编辑入口）
     * @param array{m_id?:mixed,date?:mixed,page?:mixed,page_size?:mixed,auth_where?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function configList(array $ctx): array
    {
        $page = intval($ctx['page'] ?? 1);
        if ($page <= 0) {
            $page = 1;
        }
        $pageSize = intval($ctx['page_size'] ?? 20);
        if ($pageSize <= 0) {
            $pageSize = 20;
        }
        if ($pageSize > 200) {
            $pageSize = 200;
        }

        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return [
                'state' => 200,
                'msg' => '查询成功',
                'data' => [
                    'list' => [],
                    'total' => 0,
                    'page' => $page,
                    'page_size' => $pageSize,
                ],
            ];
        }

        $filterMids = $this->normalizeMachineIdInput($ctx['m_id'] ?? '');
        if ($filterMids !== []) {
            $filterMids = array_values(array_intersect($filterMids, $allowedMids));
            if ($filterMids === []) {
                return [
                    'state' => 200,
                    'msg' => '查询成功',
                    'data' => [
                        'list' => [],
                        'total' => 0,
                        'page' => $page,
                        'page_size' => $pageSize,
                    ],
                ];
            }
        } else {
            $filterMids = $allowedMids;
        }

        $monthFilter = [];
        $dateRaw = trim((string) ($ctx['date'] ?? ''));
        if ($dateRaw !== '') {
            $monthParsed = $this->parseMonthSelection($dateRaw, false);
            if (($monthParsed['state'] ?? 100) !== 200) {
                return ['state' => 100, 'msg' => strval($monthParsed['msg'] ?? '日期格式错误'), 'data' => []];
            }
            $monthFilter = is_array($monthParsed['months'] ?? null) ? $monthParsed['months'] : [];
        }

        if ($monthFilter !== []) {
            $targetMap = $this->queryTargetAmountMap($filterMids, $monthFilter);
            $configuredMids = array_values(array_filter($filterMids, function ($mid) use ($targetMap) {
                return round((float) ($targetMap[$mid] ?? 0), 2) > 0;
            }));
        } else {
            $currentMonth = date('Y-m');
            $monthlyMids = Db::name('machine_target_monthly')
                ->whereIn('m_id', $filterMids)
                ->where('month', '>=', $currentMonth)
                ->group('m_id')
                ->column('m_id');
            $defaultMids = Db::name('machine_target_group')
                ->whereIn('m_id', $filterMids)
                ->where('target_amount', '>', 0)
                ->group('m_id')
                ->column('m_id');
            $configuredMids = array_values(array_unique(array_map('intval', array_merge(
                is_array($monthlyMids) ? $monthlyMids : [],
                is_array($defaultMids) ? $defaultMids : []
            ))));
        }
        rsort($configuredMids);

        $total = count($configuredMids);
        $pageMids = array_slice($configuredMids, ($page - 1) * $pageSize, $pageSize);
        if ($pageMids === []) {
            return [
                'state' => 200,
                'msg' => '查询成功',
                'data' => [
                    'list' => [],
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                ],
            ];
        }

        $monthlyQuery = Db::name('machine_target_monthly')
            ->whereIn('m_id', $pageMids)
            ->field('m_id,month,IFNULL(SUM(target_amount),0) as target_amount')
            ->group('m_id,month')
            ->order('month', 'asc');
        if ($monthFilter !== []) {
            $monthlyQuery->whereIn('month', $monthFilter);
        } else {
            $monthlyQuery->where('month', '>=', date('Y-m'));
        }
        $monthlyRows = $monthlyQuery->select()->toArray();

        $monthMap = [];
        foreach ($monthlyRows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            $month = strval($row['month'] ?? '');
            if ($mid > 0 && $month !== '') {
                $monthMap[$mid][$month] = round((float) ($row['target_amount'] ?? 0), 2);
            }
        }

        $defaultRows = Db::name('machine_target_group')
            ->whereIn('m_id', $pageMids)
            ->where('months', '<=', date('Y-m'))
            ->field('m_id,months,target_amount,create_time')
            ->order('months', 'desc')
            ->select()
            ->toArray();
        $defaultMap = [];
        foreach ($defaultRows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if ($mid > 0 && !isset($defaultMap[$mid])) {
                $defaultMap[$mid] = $row;
            }
        }

        $machineMap = $this->queryMachineBaseInfo($pageMids);
        $list = [];
        foreach ($pageMids as $mid) {
            $prices = $monthMap[$mid] ?? [];
            $months = array_keys($prices);
            $priceList = array_values($prices);
            $machine = $machineMap[$mid] ?? ['machine_id' => '', 'machine_name' => ''];
            $default = $defaultMap[$mid] ?? [];
            $machineInfo = [
                'm_id' => $mid,
                'machine_id' => strval($machine['machine_id'] ?? ''),
                'machine_name' => strval($machine['machine_name'] ?? ''),
                'label' => strval($machine['machine_id'] ?? '') . ' ' . strval($machine['machine_name'] ?? ''),
            ];

            $list[] = [
                'id' => $mid,
                'm_id' => strval($mid),
                'm_id_list' => [$mid],
                'machines' => [$machineInfo],
                'date' => implode(',', $months),
                'months' => $months,
                'price' => implode(',', array_map('strval', $priceList)),
                'price_list' => $priceList,
                'target_amount' => round((float) ($default['target_amount'] ?? 0), 2),
                'target_month' => strval($default['months'] ?? ''),
                'create_time' => intval($default['create_time'] ?? 0),
            ];
        }

        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ],
        ];
    }

    /**
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function detail(int $mId, array $authWhere = []): array
    {
        if ($mId <= 0) {
            return ['state' => 100, 'msg' => 'm_id不能为空', 'data' => []];
        }

        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return ['state' => 100, 'msg' => '当前账号无权限查看该设备', 'data' => []];
        }
        if (!in_array($mId, $allowedMids, true)) {
            return ['state' => 100, 'msg' => '当前账号无权限查看该设备', 'data' => []];
        }

        $currentMonth = date('Y-m');
        $rows = Db::name('machine_target_monthly')
            ->where('m_id', '=', $mId)
            ->where('month', '>=', $currentMonth)
            ->field('month, IFNULL(MAX(target_amount),0) as target_amount')
            ->group('month')
            ->order('month', 'asc')
            ->select()
            ->toArray();

        $machineRow = Db::name('machine')
            ->where('m_id', '=', $mId)
            ->field('machine_id,machine_name')
            ->find();
        $defaultRow = Db::name('machine_target_group')
            ->where('m_id', '=', $mId)
            ->where('months', '<=', $currentMonth)
            ->field('months,target_amount')
            ->order('months', 'desc')
            ->find();

        $date = [];
        $info = [];
        foreach ($rows as $row) {
            $month = strval($row['month'] ?? '');
            if ($month === '') {
                continue;
            }

            $price = round((float) ($row['target_amount'] ?? 0), 2);
            $date[] = $month;
            $info[] = [
                'date' => $month,
                'price' => $price,
            ];
        }

        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'm_id' => $mId,
                'machine_id' => strval($machineRow['machine_id'] ?? ''),
                'machine_name' => strval($machineRow['machine_name'] ?? ''),
                'date' => $date,
                'info' => $info,
                'target_amount' => round((float) ($defaultRow['target_amount'] ?? 0), 2),
                'target_month' => strval($defaultRow['months'] ?? ''),
            ],
        ];
    }

    /**
     * @param mixed $rawDate
     * @param mixed $rawPrice
     * @return array<string,mixed>
     */
    protected function parseMonthPriceInput($rawDate, $rawPrice): array
    {
        $dateRaw = trim((string) $rawDate);
        if ($dateRaw === '') {
            return ['state' => 100, 'msg' => '日期不能为空'];
        }

        $months = [];
        if (preg_match('/^\s*(\d{4}-(0[1-9]|1[0-2]))\s*[~～]\s*(\d{4}-(0[1-9]|1[0-2]))\s*$/', $dateRaw, $match)) {
            $startMonth = $match[1];
            $endMonth = $match[3];
            if ($startMonth > $endMonth) {
                $tmp = $startMonth;
                $startMonth = $endMonth;
                $endMonth = $tmp;
            }

            $cursor = $startMonth;
            while ($cursor <= $endMonth) {
                $months[] = $cursor;
                $cursor = date('Y-m', strtotime($cursor . '-01 +1 month'));
            }
        } else {
            $monthParts = preg_split('/[，,\s]+/', $dateRaw) ?: [];
            $monthMap = [];
            foreach ($monthParts as $month) {
                $month = trim($month);
                if ($month === '') {
                    continue;
                }
                if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                    return ['state' => 100, 'msg' => '日期格式错误，示例：2026-05,2026-06'];
                }
                if (isset($monthMap[$month])) {
                    return ['state' => 100, 'msg' => '日期中存在重复月份'];
                }
                $monthMap[$month] = $month;
            }
            $months = array_values($monthMap);
        }
        if ($months === []) {
            return ['state' => 100, 'msg' => '日期不能为空'];
        }

        $priceRaw = trim((string) $rawPrice);
        if ($priceRaw === '') {
            return ['state' => 100, 'msg' => '目标金额不能为空'];
        }

        $priceParts = preg_split('/[，,\s]+/', $priceRaw) ?: [];
        $prices = [];
        foreach ($priceParts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (!is_numeric($part)) {
                return ['state' => 100, 'msg' => '目标金额格式错误'];
            }
            $price = round((float) $part, 2);
            if ($price <= 0) {
                return ['state' => 100, 'msg' => '目标金额必须大于0'];
            }
            $prices[] = $price;
        }

        if ($prices === []) {
            return ['state' => 100, 'msg' => '目标金额不能为空'];
        }
        if (count($months) !== count($prices)) {
            return ['state' => 100, 'msg' => '日期数量与金额数量不一致'];
        }

        $pairs = [];
        for ($i = 0; $i < count($months); $i++) {
            $pairs[] = [
                'month' => $months[$i],
                'price' => $prices[$i],
            ];
        }

        usort($pairs, function ($a, $b) {
            $ma = strval($a['month'] ?? '');
            $mb = strval($b['month'] ?? '');
            if ($ma === $mb) {
                return 0;
            }
            return $ma < $mb ? -1 : 1;
        });

        $sortedMonths = [];
        $sortedPrices = [];
        $monthPriceMap = [];
        foreach ($pairs as $pair) {
            $month = strval($pair['month'] ?? '');
            $price = round((float) ($pair['price'] ?? 0), 2);
            if ($month === '' || $price <= 0) {
                continue;
            }
            $sortedMonths[] = $month;
            $sortedPrices[] = $price;
            $monthPriceMap[$month] = $price;
        }

        if ($sortedMonths === [] || $sortedPrices === [] || $monthPriceMap === []) {
            return ['state' => 100, 'msg' => '日期或金额格式错误'];
        }

        return [
            'state' => 200,
            'months' => $sortedMonths,
            'price_list' => $sortedPrices,
            'month_price_map' => $monthPriceMap,
        ];
    }

    /**
     * @param mixed $raw
     * @return array{state:int,msg?:string,amount?:float}
     */
    protected function parseDefaultTargetAmount($raw): array
    {
        if ($raw === null || $raw === '') {
            return ['state' => 200, 'amount' => 0.0];
        }
        if (is_array($raw) || is_object($raw) || !is_numeric($raw)) {
            return ['state' => 100, 'msg' => '默认目标金额格式错误'];
        }

        $amount = round((float) $raw, 2);
        if ($amount < 0) {
            return ['state' => 100, 'msg' => '默认目标金额不能小于0'];
        }
        if ((float) $raw > 0 && $amount <= 0) {
            return ['state' => 100, 'msg' => '默认目标金额最小为0.01'];
        }
        if ($amount > 9999999999.99) {
            return ['state' => 100, 'msg' => '默认目标金额超出允许范围'];
        }

        return ['state' => 200, 'amount' => $amount];
    }

    /**
     * @param array{date?:mixed} $ctx
     * @return array<string,mixed>
     */
    public function devices(array $ctx): array
    {
        $parsed = $this->parseMonthSelection($ctx['date'] ?? date('Y-m'), true);
        if (($parsed['state'] ?? 100) !== 200) {
            return [
                'date' => strval($ctx['date'] ?? ''),
                'months' => [],
                'list' => [],
            ];
        }
        $months = $parsed['months'];

        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return [
                'date' => implode(',', $months),
                'months' => $months,
                'list' => [],
            ];
        }

        $targetMap = $this->queryTargetAmountMap($allowedMids, $months);
        $configuredMids = [];
        foreach ($allowedMids as $mid) {
            if (round((float) ($targetMap[$mid] ?? 0), 2) > 0) {
                $configuredMids[] = $mid;
            }
        }
        if ($configuredMids === []) {
            return [
                'date' => implode(',', $months),
                'months' => $months,
                'list' => [],
            ];
        }

        $rows = Db::name('machine')
            ->whereIn('m_id', $configuredMids)
            ->field('m_id,machine_id,machine_name')
            ->order('m_id', 'desc')
            ->select()
            ->toArray();
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'm_id' => intval($row['m_id'] ?? 0),
                'machine_id' => strval($row['machine_id'] ?? ''),
                'machine_name' => strval($row['machine_name'] ?? ''),
                'label' => strval($row['machine_id'] ?? '') . ' ' . strval($row['machine_name'] ?? ''),
            ];
        }

        return [
            'date' => implode(',', $months),
            'months' => $months,
            'list' => $list,
        ];
    }

    /**
     * @param array{m_id?:mixed,date?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function stats(array $ctx): array
    {
        return $this->statsSummary($ctx);
    }

    /**
     * @param array{m_id?:mixed,date?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function statsSummary(array $ctx): array
    {
        $res = $this->statsCore($ctx);
        if (($res['state'] ?? 100) !== 200) {
            return $res;
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'summary' => $data['summary'] ?? [],
                'summaryCompare' => $data['summaryCompare'] ?? [],
            ],
        ];
    }

    /**
     * @param array{m_id?:mixed,date?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function statsList(array $ctx): array
    {
        $res = $this->statsCore($ctx);
        if (($res['state'] ?? 100) !== 200) {
            return $res;
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'list' => $data['list'] ?? [],
                'total' => intval($data['total'] ?? 0),
            ],
        ];
    }

    /**
     * @param array{m_id?:mixed,date?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function exportStatsList(array $ctx): array
    {
        $res = $this->statsCore($ctx);
        if (($res['state'] ?? 100) !== 200) {
            return $res;
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        $list = is_array($data['list'] ?? null) ? $data['list'] : [];

        $title = [
            'machine_id' => '设备编号',
            'machine_name' => '设备名称',
            'target_amount' => '目标金额',
            'full_channel_amount' => '上满货金额',
            'full_channel_cost' => '上满货成本',
            'sale_amount' => '销售额',
            'cost_amount' => '成本',
            'estimated_profit' => '预估毛利',
            'achievement_rate' => '达成率(%)',
            'turnover_ratio' => '周转比',
            'turnover_days' => '周转周期(天)',
            'prev_sale_amount' => '上期销售额',
            'prev_cost_amount' => '上期成本',
            'prev_profit_amount' => '上期预估毛利',
            'prev_turnover_ratio' => '上期周转比',
            'prev_turnover_days' => '上期周转周期(天)',
        ];

        $filename = '设备目标统计列表-' . date('Ymd');
        return $this->enqueueExport('统计报表-设备目标统计列表', $filename, $title, $list);
    }

    /**
     * @param array{m_id?:mixed,date?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    protected function statsCore(array $ctx): array
    {
        $parsed = $this->parseMonthSelection($ctx['date'] ?? date('Y-m'), true);
        if (($parsed['state'] ?? 100) !== 200) {
            return ['state' => 100, 'msg' => strval($parsed['msg'] ?? '月份格式错误'), 'data' => []];
        }

        $months = $parsed['months'];
        $start = intval($parsed['start']);
        $end = intval($parsed['end']);
        $prevStart = intval($parsed['prevStart']);
        $prevEnd = intval($parsed['prevEnd']);
        $dayCount = max(1, intval($parsed['dayCount']));

        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return [
                'state' => 200,
                'msg' => '查询成功',
                'data' => $this->emptyStatsPayload($months, $ctx['m_id'] ?? ''),
            ];
        }

        $devices = $this->devices([
            'date' => implode(',', $months),
            'auth_where' => $authWhere,
        ]);
        $deviceOptions = is_array($devices['list'] ?? null) ? $devices['list'] : [];
        $configuredMids = [];
        foreach ($deviceOptions as $item) {
            $mid = intval($item['m_id'] ?? 0);
            if ($mid > 0) {
                $configuredMids[] = $mid;
            }
        }
        $configuredMids = array_values(array_unique($configuredMids));

        $selectedMids = $this->normalizeMachineIdInput($ctx['m_id'] ?? '');
        if ($selectedMids !== []) {
            $selectedMids = array_values(array_intersect($selectedMids, $allowedMids));
        }

        $baseMids = $selectedMids !== [] ? $selectedMids : $configuredMids;
        if ($baseMids !== []) {
            $baseMids = array_values(array_intersect($baseMids, $configuredMids));
        }

        if ($baseMids === []) {
            return [
                'state' => 200,
                'msg' => '查询成功',
                'data' => $this->emptyStatsPayload($months, $ctx['m_id'] ?? '', $deviceOptions),
            ];
        }

        $machineMap = $this->queryMachineBaseInfo($baseMids);
        $targetMap = $this->queryTargetAmountMap($baseMids, $months);
        $fullChannelMap = $this->queryFullChannelAmountCostMap($baseMids);
        $currentMap = $this->queryAmountCostMap($baseMids, $start, $end);
        $prevMap = $this->queryAmountCostMap($baseMids, $prevStart, $prevEnd);

        $list = [];
        $sumCurrentSale = 0;
        $sumCurrentCost = 0;
        $sumFullChannelAmount = 0;
        $sumFullChannelCost = 0;
        $sumCurrentTarget = 0;
        $sumPrevSale = 0;
        $sumPrevCost = 0;

        foreach ($baseMids as $mid) {
            $machine = $machineMap[$mid] ?? ['machine_id' => '', 'machine_name' => ''];
            $current = $currentMap[$mid] ?? ['sale' => 0, 'cost' => 0];
            $prev = $prevMap[$mid] ?? ['sale' => 0, 'cost' => 0];
            $fullChannel = $fullChannelMap[$mid] ?? ['amount' => 0, 'cost' => 0];

            $targetAmount = round((float) ($targetMap[$mid] ?? 0), 2);
            $fullChannelAmount = round((float) ($fullChannel['amount'] ?? 0), 2);
            $fullChannelCost = round((float) ($fullChannel['cost'] ?? 0), 2);
            $saleAmount = round((float) ($current['sale'] ?? 0), 2);
            $costAmount = round((float) ($current['cost'] ?? 0), 2);
            $profitAmount = round($saleAmount - $costAmount, 2);

            $prevSale = round((float) ($prev['sale'] ?? 0), 2);
            $prevCost = round((float) ($prev['cost'] ?? 0), 2);
            $prevProfit = round($prevSale - $prevCost, 2);

            $achievementRate = $targetAmount > 0 ? round($saleAmount / $targetAmount * 100, 2) : 0;
            $turnoverRatio = $fullChannelCost > 0 ? round($saleAmount / $fullChannelCost, 2) : 0;
            $turnoverDays = $turnoverRatio > 0 ? round($dayCount / $turnoverRatio, 1) : 0;

            $prevTurnoverRatio = $fullChannelCost > 0 ? round($prevSale / $fullChannelCost, 2) : 0;
            $prevTurnoverDays = $prevTurnoverRatio > 0 ? round($dayCount / $prevTurnoverRatio, 1) : 0;

            $list[] = [
                'm_id' => $mid,
                'machine_id' => strval($machine['machine_id'] ?? ''),
                'machine_name' => strval($machine['machine_name'] ?? ''),
                'full_channel_amount' => $fullChannelAmount,
                'full_channel_cost' => $fullChannelCost,
                'sale_amount' => $saleAmount,
                'cost_amount' => $costAmount,
                'estimated_profit' => $profitAmount,
                'cost_price' => $costAmount,
                'profit_amount' => $profitAmount,
                'target_amount' => $targetAmount,
                'achievement_rate' => $achievementRate,
                'turnover_ratio' => $turnoverRatio,
                'turnover_days' => $turnoverDays,
                'prev_sale_amount' => $prevSale,
                'prev_cost_amount' => $prevCost,
                'prev_profit_amount' => $prevProfit,
                'prev_turnover_ratio' => $prevTurnoverRatio,
                'prev_turnover_days' => $prevTurnoverDays,
            ];

            $sumCurrentSale += $saleAmount;
            $sumCurrentCost += $costAmount;
            $sumFullChannelAmount += $fullChannelAmount;
            $sumFullChannelCost += $fullChannelCost;
            $sumCurrentTarget += $targetAmount;
            $sumPrevSale += $prevSale;
            $sumPrevCost += $prevCost;
        }

        $sumCurrentSale = round($sumCurrentSale, 2);
        $sumCurrentCost = round($sumCurrentCost, 2);
        $sumFullChannelAmount = round($sumFullChannelAmount, 2);
        $sumFullChannelCost = round($sumFullChannelCost, 2);
        $sumCurrentProfit = round($sumCurrentSale - $sumCurrentCost, 2);
        $sumCurrentRate = $sumCurrentTarget > 0 ? round($sumCurrentSale / $sumCurrentTarget * 100, 2) : 0;
        $sumCurrentRatio = $sumFullChannelCost > 0 ? round($sumCurrentSale / $sumFullChannelCost, 2) : 0;
        $sumCurrentDays = $sumCurrentRatio > 0 ? round($dayCount / $sumCurrentRatio, 1) : 0;

        $sumPrevSale = round($sumPrevSale, 2);
        $sumPrevCost = round($sumPrevCost, 2);
        $sumPrevProfit = round($sumPrevSale - $sumPrevCost, 2);
        $sumPrevRate = $sumCurrentTarget > 0 ? round($sumPrevSale / $sumCurrentTarget * 100, 2) : 0;
        $sumPrevRatio = $sumFullChannelCost > 0 ? round($sumPrevSale / $sumFullChannelCost, 2) : 0;
        $sumPrevDays = $sumPrevRatio > 0 ? round($dayCount / $sumPrevRatio, 1) : 0;

        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'filters' => [
                    'date' => implode('~', [$months[0], $months[count($months) - 1]]),
                    'months' => $months,
                    'm_id' => $baseMids,
                ],
                'machineOptions' => $deviceOptions,
                'summary' => [
                    'full_channel_amount' => $sumFullChannelAmount,
                    'full_channel_cost' => $sumFullChannelCost,
                    'sale_amount' => $sumCurrentSale,
                    'cost_amount' => $sumCurrentCost,
                    'estimated_profit' => $sumCurrentProfit,
                    'achievement_rate' => $sumCurrentRate,
                    'turnover_ratio' => $sumCurrentRatio,
                    'turnover_days' => $sumCurrentDays,
                ],
                'summaryCompare' => [
                    'full_channel_amount' => $this->compare($sumFullChannelAmount, $sumFullChannelAmount),
                    'full_channel_cost' => $this->compare($sumFullChannelCost, $sumFullChannelCost),
                    'sale_amount' => $this->compare($sumCurrentSale, $sumPrevSale),
                    'cost_amount' => $this->compare($sumCurrentCost, $sumPrevCost),
                    'estimated_profit' => $this->compare($sumCurrentProfit, $sumPrevProfit),
                    'achievement_rate' => $this->compare($sumCurrentRate, $sumPrevRate),
                    'turnover_ratio' => $this->compare($sumCurrentRatio, $sumPrevRatio),
                    'turnover_days' => $this->compare($sumCurrentDays, $sumPrevDays),
                ],
                'list' => $list,
                'total' => count($list),
            ],
        ];
    }

    /**
     * @return int[]|null
     */
    protected function resolveAuthorizedMachineIds(array $authWhere = []): array
    {
        $query = Db::name('machine');

        foreach ($authWhere as $k => $v) {
            if (is_array($v) && isset($v[0], $v[1])) {
                $field = strval($v[0]);
                $op = strval($v[1]);
                $value = $v[2] ?? null;
                if ($field === '' || $op === '') {
                    continue;
                }
                if ($value === null) {
                    continue;
                }
                $query->where($field, $op, $value);
                continue;
            }

            if (is_string($k) && $k !== '') {
                $query->where($k, '=', $v);
            }
        }

        $mids = $query->column('m_id');
        return array_values(array_unique(array_map('intval', is_array($mids) ? $mids : [])));
    }

    /**
     * @param mixed $raw
     * @return int[]
     */
    protected function normalizeMachineIdInput($raw): array
    {
        $parts = [];
        if (is_array($raw)) {
            foreach ($raw as $v) {
                if (is_array($v)) {
                    continue;
                }
                $parts[] = (string) $v;
            }
        } else {
            $str = trim((string) $raw);
            if ($str !== '') {
                $parts = preg_split('/[，,\s]+/', $str) ?: [];
            }
        }

        $out = [];
        foreach ($parts as $part) {
            $val = intval(trim((string) $part));
            if ($val > 0) {
                $out[] = $val;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    /**
     * @return string[]
     */
    protected function normalizeMonthList(string $raw): array
    {
        $parts = preg_split('/[，,\s]+/', trim($raw)) ?: [];
        $months = [];
        foreach ($parts as $month) {
            $month = trim($month);
            if ($month === '') {
                continue;
            }
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                $months[] = $month;
            }
        }

        $months = array_values(array_unique($months));
        sort($months);
        return $months;
    }

    /**
     * @param mixed $raw
     * @return array<string,mixed>
     */
    protected function parseMonthSelection($raw, bool $continuousRequired): array
    {
        $dateRaw = trim((string) $raw);
        if ($dateRaw === '') {
            $dateRaw = date('Y-m');
        }

        $months = [];
        if (preg_match('/^\s*(\d{4}-(0[1-9]|1[0-2]))\s*[~～]\s*(\d{4}-(0[1-9]|1[0-2]))\s*$/', $dateRaw, $match)) {
            $startMonth = $match[1];
            $endMonth = $match[3];
            if ($startMonth > $endMonth) {
                $tmp = $startMonth;
                $startMonth = $endMonth;
                $endMonth = $tmp;
            }

            $cursor = $startMonth;
            while ($cursor <= $endMonth) {
                $months[] = $cursor;
                $cursor = date('Y-m', strtotime($cursor . '-01 +1 month'));
            }
        } else {
            $months = $this->normalizeMonthList($dateRaw);
        }

        if ($months === []) {
            return ['state' => 100, 'msg' => '日期格式错误，示例：2026-01~2026-05 或 2026-05,2026-06'];
        }

        if ($continuousRequired && !$this->isContinuousMonths($months)) {
            return ['state' => 100, 'msg' => '日期必须为连续月份'];
        }

        sort($months);
        $first = $months[0];
        $last = $months[count($months) - 1];
        $start = $this->monthBounds($first)['start'];
        $end = $this->monthBounds($last)['end'];

        $monthCount = count($months);
        $prevFirst = date('Y-m', strtotime($first . '-01 -' . $monthCount . ' month'));
        $prevLast = date('Y-m', strtotime($last . '-01 -' . $monthCount . ' month'));
        $prevStart = $this->monthBounds($prevFirst)['start'];
        $prevEnd = $this->monthBounds($prevLast)['end'];

        $dayCount = intval(floor(($end - $start) / 86400) + 1);

        return [
            'state' => 200,
            'months' => $months,
            'start' => $start,
            'end' => $end,
            'prevStart' => $prevStart,
            'prevEnd' => $prevEnd,
            'dayCount' => max(1, $dayCount),
        ];
    }

    /**
     * @param string[] $months
     */
    protected function isContinuousMonths(array $months): bool
    {
        if ($months === []) {
            return false;
        }

        sort($months);
        for ($i = 1; $i < count($months); $i++) {
            $expect = date('Y-m', strtotime($months[$i - 1] . '-01 +1 month'));
            if ($months[$i] !== $expect) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{start:int,end:int}
     */
    protected function monthBounds(string $month): array
    {
        $start = strtotime($month . '-01 00:00:00');
        $end = strtotime(date('Y-m-t 23:59:59', strtotime($month . '-01')));

        return [
            'start' => $start === false ? 0 : intval($start),
            'end' => $end === false ? 0 : intval($end),
        ];
    }

    /**
     * @param int[] $mIds
     * @return array<int,array{machine_id:string,machine_name:string}>
     */
    protected function queryMachineBaseInfo(array $mIds): array
    {
        if ($mIds === []) {
            return [];
        }

        $rows = Db::name('machine')
            ->whereIn('m_id', $mIds)
            ->field('m_id,machine_id,machine_name')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $map[$mid] = [
                'machine_id' => strval($row['machine_id'] ?? ''),
                'machine_name' => strval($row['machine_name'] ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @param int[] $mIds
     * @param string[] $months
     * @return array<int,float>
     */
    protected function queryTargetAmountMap(array $mIds, array $months): array
    {
        if ($mIds === [] || $months === []) {
            return [];
        }

        $rows = Db::name('machine_target_monthly')
            ->whereIn('m_id', $mIds)
            ->whereIn('month', $months)
            ->field('m_id,month,IFNULL(SUM(target_amount),0) as target_amount')
            ->group('m_id,month')
            ->select()
            ->toArray();

        $monthlyMap = [];
        foreach ($rows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            $month = strval($row['month'] ?? '');
            if ($mid <= 0 || $month === '') {
                continue;
            }
            $monthlyMap[$mid][$month] = round((float) ($row['target_amount'] ?? 0), 2);
        }

        $defaultMap = $this->queryDefaultTargetAmountByMonthMap($mIds, $months);
        return $this->mergeTargetAmountMap($mIds, $months, $monthlyMap, $defaultMap);
    }

    /**
     * @param int[] $mIds
     * @param string[] $months
     * @param array<int,array<string,float>> $monthlyMap
     * @param array<int,array<string,float>> $defaultMap
     * @return array<int,float>
     */
    protected function mergeTargetAmountMap(
        array $mIds,
        array $months,
        array $monthlyMap,
        array $defaultMap
    ): array {
        $map = [];
        foreach ($mIds as $mid) {
            $mid = intval($mid);
            if ($mid <= 0) {
                continue;
            }

            $total = 0.0;
            foreach ($months as $month) {
                $month = strval($month);
                // 月度目标按记录是否存在判断，存在时始终优先于默认目标。
                if (isset($monthlyMap[$mid]) && array_key_exists($month, $monthlyMap[$mid])) {
                    $total += (float) $monthlyMap[$mid][$month];
                    continue;
                }
                $total += (float) ($defaultMap[$mid][$month] ?? 0);
            }
            $map[$mid] = round($total, 2);
        }

        return $map;
    }

    /**
     * @param int[] $mIds
     * @param string[] $months
     * @return array<int,array<string,float>>
     */
    protected function queryDefaultTargetAmountByMonthMap(array $mIds, array $months): array
    {
        $mIds = array_values(array_unique(array_filter(array_map('intval', $mIds), function ($mid) {
            return $mid > 0;
        })));
        $months = array_values(array_unique(array_filter(array_map('strval', $months), function ($month) {
            return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1;
        })));
        if ($mIds === [] || $months === []) {
            return [];
        }
        sort($months);

        $rows = Db::name('machine_target_group')
            ->whereIn('m_id', $mIds)
            ->where('months', '<=', $months[count($months) - 1])
            ->field('m_id,months,target_amount')
            ->order('m_id', 'asc')
            ->order('months', 'asc')
            ->select()
            ->toArray();

        return $this->resolveDefaultTargetAmountByMonthMap($mIds, $months, $rows);
    }

    /**
     * @param int[] $mIds
     * @param string[] $months
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,float>>
     */
    protected function resolveDefaultTargetAmountByMonthMap(
        array $mIds,
        array $months,
        array $rows
    ): array {
        $historyMap = [];
        foreach ($rows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            $effectiveMonth = strval($row['months'] ?? '');
            if ($mid <= 0 || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $effectiveMonth) !== 1) {
                continue;
            }
            $historyMap[$mid][$effectiveMonth] = round((float) ($row['target_amount'] ?? 0), 2);
        }

        $map = [];
        foreach ($mIds as $mid) {
            $history = $historyMap[$mid] ?? [];
            if ($history === []) {
                continue;
            }
            ksort($history);

            $effectiveMonths = array_keys($history);
            $historyIndex = 0;
            $historyCount = count($effectiveMonths);
            $activeAmount = 0.0;
            foreach ($months as $month) {
                while (
                    $historyIndex < $historyCount
                    && strval($effectiveMonths[$historyIndex]) <= $month
                ) {
                    $activeAmount = round(
                        (float) ($history[$effectiveMonths[$historyIndex]] ?? 0),
                        2
                    );
                    $historyIndex++;
                }
                if ($activeAmount > 0) {
                    $map[$mid][$month] = $activeAmount;
                }
            }
        }

        return $map;
    }

    /**
     * @param int[] $mIds
     * @return array<int,array{sale:float,cost:float}>
     */
    protected function queryAmountCostMap(array $mIds, int $start, int $end): array
    {
        if ($mIds === [] || $start <= 0 || $end <= 0 || $start > $end) {
            return [];
        }

        $saleRows = Db::name('sale_orders')->alias('so')
            ->where('so.pay_status', 3)
            ->where('so.create_date', '>=', $start)
            ->where('so.create_date', '<=', $end)
            ->whereIn('so.m_id', $mIds)
            ->field('so.m_id, IFNULL(SUM(so.total_price - so.refund_amount),0) as sale_amount')
            ->group('so.m_id')
            ->select()
            ->toArray();

        $costRows = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->where('so.pay_status', 3)
            ->where('so.create_date', '>=', $start)
            ->where('so.create_date', '<=', $end)
            ->whereIn('so.m_id', $mIds)
            ->field('so.m_id, IFNULL(SUM((IFNULL(sod.success_quantity,0) - IFNULL(sod.refund_quantity,0)) * IFNULL(sod.cost_price,0)),0) as cost_amount')
            ->group('so.m_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($mIds as $mid) {
            $map[$mid] = ['sale' => 0, 'cost' => 0];
        }

        foreach ($saleRows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if (!isset($map[$mid])) {
                continue;
            }
            $map[$mid]['sale'] = round((float) ($row['sale_amount'] ?? 0), 2);
        }

        foreach ($costRows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if (!isset($map[$mid])) {
                continue;
            }
            $map[$mid]['cost'] = round((float) ($row['cost_amount'] ?? 0), 2);
        }

        return $map;
    }

    /**
     * 货道上满货金额/成本（仅统计 g_id > 0 的货道）
     * @param int[] $mIds
     * @return array<int,array{amount:float,cost:float}>
     */
    protected function queryFullChannelAmountCostMap(array $mIds): array
    {
        if ($mIds === []) {
            return [];
        }

        $rows = Db::name('machine_channel')
            ->whereIn('m_id', $mIds)
            ->where('g_id', '>', 0)
            ->field('m_id, IFNULL(SUM(IFNULL(capacity,0) * IFNULL(retail_price,0)),0) as full_amount, IFNULL(SUM(IFNULL(capacity,0) * IFNULL(cost_price,0)),0) as full_cost')
            ->group('m_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($mIds as $mid) {
            $map[$mid] = ['amount' => 0, 'cost' => 0];
        }

        foreach ($rows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if (!isset($map[$mid])) {
                continue;
            }
            $map[$mid]['amount'] = round((float) ($row['full_amount'] ?? 0), 2);
            $map[$mid]['cost'] = round((float) ($row['full_cost'] ?? 0), 2);
        }

        return $map;
    }

    /**
     * @param array<string,string> $title
     * @param array<int,array<string,mixed>> $list
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    protected function enqueueExport(string $position, string $filename, array $title, array $list): array
    {
        $insert = [
            'request_time' => time(),
            'export_position' => $position,
            'file_name' => $filename,
            'status' => 1,
            'ao_id' => intval($this->manager['ao_id'] ?? 0),
            'creator' => intval($this->manager['manager_id'] ?? 0),
            'create_time' => time(),
        ];

        $exportId = intval(Db::name('export_log')->insertGetId($insert));
        if ($exportId <= 0) {
            return ['state' => 100, 'msg' => '导出任务创建失败', 'data' => []];
        }

        $payload = [
            'export_id' => $exportId,
            'filename' => $filename,
            'title' => $title,
            'list' => $list,
            'otherData' => [],
        ];

        $mqResult = MqProducer::export($payload);
        if ($mqResult !== 'OK') {
            Db::name('export_log')->where('export_id', $exportId)->update(['status' => 4]);
            return ['state' => 100, 'msg' => '导出任务提交失败：' . strval($mqResult), 'data' => []];
        }

        return [
            'state' => 200,
            'msg' => '导出任务已提交，请稍后在导出记录查看',
            'data' => ['export_id' => $exportId],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function emptyStatsPayload(array $months, $rawMids, array $deviceOptions = []): array
    {
        $selected = $this->normalizeMachineIdInput($rawMids);
        return [
            'filters' => [
                'date' => implode('~', [$months[0], $months[count($months) - 1]]),
                'months' => $months,
                'm_id' => $selected,
            ],
            'machineOptions' => $deviceOptions,
            'summary' => [
                'full_channel_amount' => 0,
                'full_channel_cost' => 0,
                'sale_amount' => 0,
                'cost_amount' => 0,
                'estimated_profit' => 0,
                'achievement_rate' => 0,
                'turnover_ratio' => 0,
                'turnover_days' => 0,
            ],
            'summaryCompare' => [
                'full_channel_amount' => $this->compare(0, 0),
                'full_channel_cost' => $this->compare(0, 0),
                'sale_amount' => $this->compare(0, 0),
                'cost_amount' => $this->compare(0, 0),
                'estimated_profit' => $this->compare(0, 0),
                'achievement_rate' => $this->compare(0, 0),
                'turnover_ratio' => $this->compare(0, 0),
                'turnover_days' => $this->compare(0, 0),
            ],
            'list' => [],
            'total' => 0,
        ];
    }

    /**
     * @return array{current:float,previous:float,delta:float,rate:float}
     */
    protected function compare(float $current, float $previous): array
    {
        $delta = round($current - $previous, 2);
        $rate = 0.0;
        if ($previous != 0) {
            $rate = round($delta / abs($previous) * 100, 2);
        } elseif ($current != 0) {
            $rate = 100.0;
        }

        return [
            'current' => round($current, 2),
            'previous' => round($previous, 2),
            'delta' => $delta,
            'rate' => $rate,
        ];
    }
}
