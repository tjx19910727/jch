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
        $res = $this->statsCore($ctx, true);
        if (($res['state'] ?? 100) !== 200) {
            return $res;
        }

        $data = is_array($res['data'] ?? null) ? $res['data'] : [];
        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'query_scope' => $data['query_scope'] ?? [],
                'summary' => $data['summary'] ?? [],
                'summaryCompare' => $data['summaryCompare'] ?? [],
                'forecast' => $data['forecast'] ?? [],
                'device_progress' => $data['device_progress'] ?? [],
                'trend' => $data['trend'] ?? [],
                'updated_at' => strval($data['updated_at'] ?? ''),
            ],
        ];
    }

    /**
     * @param mixed $ctx
     * @return array
     */
    public function statsList($ctx)
    {
        $ctx = is_array($ctx) ? $ctx : [];
        $page = max(1, intval($ctx['page'] ?? 1));
        $pageNum = intval($ctx['pageNum'] ?? 25);
        if ($pageNum <= 0) {
            $pageNum = 25;
        }

        $parsed = $this->parseMonthSelection($ctx['date'] ?? date('Y-m'), true);
        if (($parsed['state'] ?? 100) !== 200) {
            return ['state' => 100, 'msg' => strval($parsed['msg'] ?? '月份格式错误'), 'data' => []];
        }

        $months = is_array($parsed['months'] ?? null) ? $parsed['months'] : [];
        $start = intval($parsed['start'] ?? 0);
        $end = intval($parsed['end'] ?? 0);
        $prevStart = intval($parsed['prevStart'] ?? 0);
        $statsNow = time();
        $statEnd = min($statsNow, $end);
        $prevEnd = $this->resolveComparisonStatEnd($parsed, $statsNow);
        $dayCount = max(1, intval($parsed['dayCount'] ?? 0));
        $updatedAt = date('Y-m-d H:i:s', $statsNow);

        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return $this->emptyStatsListResult($page, $pageNum, $updatedAt);
        }

        $selectedMids = $this->normalizeMachineIdInput($ctx['m_id'] ?? '');
        $hasMachineFilter = $selectedMids !== [];
        $candidateMids = $hasMachineFilter
            ? array_values(array_intersect($selectedMids, $allowedMids))
            : $allowedMids;
        if ($candidateMids === []) {
            return $this->emptyStatsListResult($page, $pageNum, $updatedAt);
        }

        // 这里只加载目标配置。销售额、成本及排序交给数据库处理。
        $targetByMonthMap = $this->queryEffectiveTargetAmountByMonthMap($candidateMids, $months);
        $candidateTargetMap = $this->sumTargetAmountByMonthMap(
            $candidateMids,
            $months,
            $targetByMonthMap
        );
        $baseMids = [];
        $targetMap = [];
        $paceMap = [];
        foreach ($candidateMids as $mid) {
            $mid = intval($mid);
            $targetAmount = round((float) ($candidateTargetMap[$mid] ?? 0), 2);
            if ($mid <= 0 || $targetAmount <= 0) {
                continue;
            }
            $baseMids[] = $mid;
            $targetMap[$mid] = $targetAmount;
            $paceMap[$mid] = $this->calculateExpectedPace(
                $targetByMonthMap[$mid] ?? [],
                $months,
                $statEnd,
                $targetAmount
            );
        }
        $baseMids = array_values(array_unique($baseMids));
        if ($baseMids === []) {
            return $this->emptyStatsListResult($page, $pageNum, $updatedAt);
        }

        $total = count($baseMids);
        $rankRows = $this->queryStatsListPage(
            $baseMids,
            $targetMap,
            $paceMap,
            $start,
            $statEnd,
            $page,
            $pageNum,
            $ctx['sortName'] ?? '',
            $ctx['sort'] ?? ''
        );
        $pageMids = [];
        foreach ($rankRows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if ($mid > 0) {
                $pageMids[] = $mid;
            }
        }

        $fullChannelMap = $this->queryFullChannelAmountCostMap($pageMids);
        $currentMap = $statEnd >= $start
            ? $this->queryAmountCostMap($pageMids, $start, $statEnd)
            : [];
        $prevMap = $this->queryAmountCostMap($pageMids, $prevStart, $prevEnd);
        $list = $this->buildStatsListRows(
            $rankRows,
            $targetMap,
            $paceMap,
            $fullChannelMap,
            $currentMap,
            $prevMap,
            $dayCount
        );
        $onTrackCount = $this->queryStatsListOnTrackCount(
            $baseMids,
            $targetMap,
            $paceMap,
            $start,
            $statEnd
        );
        $lastPage = intval(ceil($total / $pageNum));

        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'list' => $list,
                'total' => $total,
                'per_page' => $pageNum,
                'current_page' => $page,
                'last_page' => $lastPage,
                'on_track_count' => $onTrackCount,
                'updated_at' => $updatedAt,
            ],
        ];
    }

    /**
     * 数据库完成全局排序和分页，只返回当前页设备。
     */
    protected function queryStatsListPage(
        $mIds,
        $targetMap,
        $paceMap,
        $start,
        $statEnd,
        $page,
        $pageNum,
        $rawSortName,
        $rawSort
    ) {
        $mIds = is_array($mIds) ? $mIds : [];
        if ($mIds === []) {
            return [];
        }

        $sortName = trim((string) $rawSortName);
        $needSaleRank = in_array(
            $sortName,
            ['完成进度', '完成金额', '销售利润', '销售目标完成差额'],
            true
        );
        $needCostRank = in_array($sortName, ['销售成本', '销售利润'], true);
        $baseSql = $this->buildStatsListRankBaseSql(
            $mIds,
            $targetMap,
            $paceMap,
            $start,
            $statEnd,
            $needSaleRank,
            $needCostRank
        );
        if ($baseSql === '') {
            return [];
        }

        $sortExpressionMap = [
            '完成进度' => 'IF(target_amount > 0, sale_amount / target_amount * 100, 0)',
            '完成金额' => 'sale_amount',
            '销售成本' => 'cost_amount',
            '销售利润' => '(sale_amount - cost_amount)',
            '销售目标' => 'target_amount',
            '销售目标完成差额' => '(target_amount - sale_amount)',
        ];
        $sort = strtolower(trim((string) $rawSort));
        if ($sort !== 'asc') {
            $sort = 'desc';
        }

        $query = Db::table($baseSql . ' stats_target_rank');
        if (isset($sortExpressionMap[$sortName])) {
            $query->fieldRaw(
                'stats_target_rank.*, '
                . $sortExpressionMap[$sortName]
                . ' AS stats_sort_value'
            );
            $query->order('stats_sort_value', $sort);
        } else {
            $query->field('stats_target_rank.*');
        }
        $query->order('m_id', 'desc');

        $offset = (max(1, intval($page)) - 1) * max(1, intval($pageNum));
        return $query
            ->limit($offset, max(1, intval($pageNum)))
            ->select()
            ->toArray();
    }

    /**
     * 在轨数量由数据库返回一个聚合值，不拉取全部设备统计结果。
     */
    protected function queryStatsListOnTrackCount($mIds, $targetMap, $paceMap, $start, $statEnd)
    {
        $mIds = is_array($mIds) ? $mIds : [];
        if ($mIds === []) {
            return 0;
        }

        $baseSql = $this->buildStatsListRankBaseSql(
            $mIds,
            $targetMap,
            $paceMap,
            $start,
            $statEnd,
            true,
            false
        );
        if ($baseSql === '') {
            return 0;
        }

        $rateExpression = 'IF(target_amount > 0, sale_amount / target_amount * 100, 0)';
        $row = Db::table($baseSql . ' stats_target_progress')
            ->fieldRaw(
                'IFNULL(SUM(IF(('
                . $rateExpression
                . ' - expected_pace) >= 0, 1, 0)), 0) AS on_track_count'
            )
            ->find();

        return intval($row['on_track_count'] ?? 0);
    }

    /**
     * 构造一行一个设备的轻量排序数据源。
     */
    protected function buildStatsListRankBaseSql(
        $mIds,
        $targetMap,
        $paceMap,
        $start,
        $statEnd,
        $includeSale,
        $includeCost
    ) {
        $mIds = is_array($mIds) ? array_values(array_unique(array_map('intval', $mIds))) : [];
        $mIds = array_values(array_filter($mIds, function ($mid) {
            return $mid > 0;
        }));
        if ($mIds === []) {
            return '';
        }

        $targetExpression = $this->buildStatsListCaseExpression('m.m_id', $targetMap, 2);
        $paceExpression = $this->buildStatsListCaseExpression('m.m_id', $paceMap, 2);
        $query = Db::name('machine')
            ->alias('m')
            ->whereIn('m.m_id', $mIds);
        $fields = [
            'm.m_id',
            'm.machine_id',
            'm.machine_name',
            $targetExpression . ' AS target_amount',
            $paceExpression . ' AS expected_pace',
        ];
        if ($includeSale) {
            $saleSql = $this->buildStatsListSaleAggregateSql($mIds, $start, $statEnd);
            if ($saleSql !== '') {
                $query->leftJoin(
                    [$saleSql => 'stats_sale'],
                    'stats_sale.m_id = m.m_id'
                );
                $fields[] = 'IFNULL(stats_sale.sale_amount, 0) AS sale_amount';
            } else {
                $fields[] = '0 AS sale_amount';
            }
        }
        if ($includeCost) {
            $costSql = $this->buildStatsListCostAggregateSql($mIds, $start, $statEnd);
            if ($costSql !== '') {
                $query->leftJoin(
                    [$costSql => 'stats_cost'],
                    'stats_cost.m_id = m.m_id'
                );
                $fields[] = 'IFNULL(stats_cost.cost_amount, 0) AS cost_amount';
            } else {
                $fields[] = '0 AS cost_amount';
            }
        }

        return $query
            ->fieldRaw(implode(',', $fields))
            ->buildSql();
    }

    protected function buildStatsListSaleAggregateSql($mIds, $start, $end)
    {
        $mIds = is_array($mIds) ? array_values(array_unique(array_map('intval', $mIds))) : [];
        $start = intval($start);
        $end = intval($end);
        if ($mIds === [] || $start <= 0 || $end <= 0 || $start > $end) {
            return '';
        }

        return Db::name('sale_orders')
            ->alias('stats_so')
            ->whereIn('stats_so.m_id', $mIds)
            ->where('stats_so.pay_status', 3)
            ->where('stats_so.create_date', '>=', $start)
            ->where('stats_so.create_date', '<=', $end)
            ->fieldRaw(
                'stats_so.m_id, '
                . 'IFNULL(SUM(stats_so.total_price - stats_so.refund_amount), 0) AS sale_amount'
            )
            ->group('stats_so.m_id')
            ->buildSql();
    }

    protected function buildStatsListCostAggregateSql($mIds, $start, $end)
    {
        $mIds = is_array($mIds) ? array_values(array_unique(array_map('intval', $mIds))) : [];
        $start = intval($start);
        $end = intval($end);
        if ($mIds === [] || $start <= 0 || $end <= 0 || $start > $end) {
            return '';
        }

        return Db::name('sale_orders')
            ->alias('stats_cost_so')
            ->leftJoin(
                'sale_orders_details stats_sod',
                'stats_sod.order_id = stats_cost_so.order_id'
            )
            ->whereIn('stats_cost_so.m_id', $mIds)
            ->where('stats_cost_so.pay_status', 3)
            ->where('stats_cost_so.create_date', '>=', $start)
            ->where('stats_cost_so.create_date', '<=', $end)
            ->fieldRaw(
                'stats_cost_so.m_id, IFNULL(SUM(('
                . 'IFNULL(stats_sod.success_quantity, 0) - IFNULL(stats_sod.refund_quantity, 0)'
                . ') * IFNULL(stats_sod.cost_price, 0)), 0) AS cost_amount'
            )
            ->group('stats_cost_so.m_id')
            ->buildSql();
    }

    protected function buildStatsListCaseExpression($field, $valueMap, $scale)
    {
        $valueMap = is_array($valueMap) ? $valueMap : [];
        $parts = [];
        foreach ($valueMap as $mid => $value) {
            $mid = intval($mid);
            if ($mid <= 0 || !is_numeric($value)) {
                continue;
            }
            $parts[] = 'WHEN ' . $mid . ' THEN '
                . number_format((float) $value, max(0, intval($scale)), '.', '');
        }
        if ($parts === []) {
            return '0';
        }

        return 'CASE ' . $field . ' ' . implode(' ', $parts) . ' ELSE 0 END';
    }

    /**
     * 当前页兼容字段继续沿用原统计口径。
     */
    protected function buildStatsListRows(
        $rankRows,
        $targetMap,
        $paceMap,
        $fullChannelMap,
        $currentMap,
        $prevMap,
        $dayCount
    ) {
        $rankRows = is_array($rankRows) ? $rankRows : [];
        $targetMap = is_array($targetMap) ? $targetMap : [];
        $paceMap = is_array($paceMap) ? $paceMap : [];
        $fullChannelMap = is_array($fullChannelMap) ? $fullChannelMap : [];
        $currentMap = is_array($currentMap) ? $currentMap : [];
        $prevMap = is_array($prevMap) ? $prevMap : [];
        $list = [];
        foreach ($rankRows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }

            $targetAmount = round((float) ($targetMap[$mid] ?? 0), 2);
            $fullChannel = $fullChannelMap[$mid] ?? ['amount' => 0, 'cost' => 0];
            $current = $currentMap[$mid] ?? ['sale' => 0, 'cost' => 0];
            $prev = $prevMap[$mid] ?? ['sale' => 0, 'cost' => 0];
            $fullChannelAmount = round((float) ($fullChannel['amount'] ?? 0), 2);
            $fullChannelCost = round((float) ($fullChannel['cost'] ?? 0), 2);
            $saleAmount = round((float) ($current['sale'] ?? 0), 2);
            $costAmount = round((float) ($current['cost'] ?? 0), 2);
            $profitAmount = round($saleAmount - $costAmount, 2);
            $prevSale = round((float) ($prev['sale'] ?? 0), 2);
            $prevCost = round((float) ($prev['cost'] ?? 0), 2);
            $prevProfit = round($prevSale - $prevCost, 2);
            $achievementRate = $targetAmount > 0
                ? round($saleAmount / $targetAmount * 100, 2)
                : 0;
            $costRate = $saleAmount > 0 ? round($costAmount / $saleAmount * 100, 2) : 0;
            $profitMargin = $saleAmount > 0 ? round($profitAmount / $saleAmount * 100, 2) : 0;
            $targetGapAmount = round(max($targetAmount - $saleAmount, 0), 2);
            $expectedPace = array_key_exists($mid, $paceMap)
                ? $paceMap[$mid]
                : null;
            $paceDelta = $expectedPace === null
                ? null
                : round($achievementRate - (float) $expectedPace, 2);
            $status = $paceDelta !== null && $paceDelta >= 0 ? 'ON_TRACK' : 'BEHIND';
            $turnoverRatio = $fullChannelCost > 0
                ? round($saleAmount / $fullChannelCost, 2)
                : 0;
            $turnoverDays = $turnoverRatio > 0
                ? round(max(1, intval($dayCount)) / $turnoverRatio, 1)
                : 0;
            $prevTurnoverRatio = $fullChannelCost > 0
                ? round($prevSale / $fullChannelCost, 2)
                : 0;
            $prevTurnoverDays = $prevTurnoverRatio > 0
                ? round(max(1, intval($dayCount)) / $prevTurnoverRatio, 1)
                : 0;

            $list[] = [
                'm_id' => $mid,
                'machine_id' => strval($row['machine_id'] ?? ''),
                'machine_name' => strval($row['machine_name'] ?? ''),
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
                'target_configured' => true,
                'cost_rate' => $costRate,
                'profit_margin' => $profitMargin,
                'target_gap_amount' => $targetGapAmount,
                'expected_pace' => $expectedPace,
                'pace_delta' => $paceDelta,
                'status' => $status,
            ];
        }

        return $list;
    }

    protected function emptyStatsListResult($page, $pageNum, $updatedAt)
    {
        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'list' => [],
                'total' => 0,
                'per_page' => max(1, intval($pageNum)),
                'current_page' => max(1, intval($page)),
                'last_page' => 0,
                'on_track_count' => 0,
                'updated_at' => strval($updatedAt),
            ],
        ];
    }

    /**
     * @param array{m_id?:mixed,date?:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function exportStatsList(array $ctx): array
    {
        $res = $this->statsCore($ctx, false);
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
    protected function statsCore(array $ctx, bool $includeDashboard = true): array
    {
        $parsed = $this->parseMonthSelection($ctx['date'] ?? date('Y-m'), true);
        if (($parsed['state'] ?? 100) !== 200) {
            return ['state' => 100, 'msg' => strval($parsed['msg'] ?? '月份格式错误'), 'data' => []];
        }

        $months = is_array($parsed['months'] ?? null) ? $parsed['months'] : [];
        $start = intval($parsed['start']);
        $end = intval($parsed['end']);
        $prevStart = intval($parsed['prevStart']);
        $statsNow = time();
        $prevEnd = $this->resolveComparisonStatEnd($parsed, $statsNow);
        $dayCount = max(1, intval($parsed['dayCount']));
        $updatedAt = date('Y-m-d H:i:s', $statsNow);
        $statEnd = min($statsNow, $end);
        $queryScope = $this->buildQueryScope($parsed, $statEnd, 0);

        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return [
                'state' => 200,
                'msg' => '查询成功',
                'data' => $this->emptyStatsPayload(
                    $months,
                    $ctx['m_id'] ?? '',
                    [],
                    $queryScope,
                    $updatedAt
                ),
            ];
        }

        $allowedTargetByMonthMap = $this->queryEffectiveTargetAmountByMonthMap($allowedMids, $months);
        $allowedTargetMap = $this->sumTargetAmountByMonthMap(
            $allowedMids,
            $months,
            $allowedTargetByMonthMap
        );
        $configuredMids = [];
        foreach ($allowedMids as $mid) {
            if (round((float) ($allowedTargetMap[$mid] ?? 0), 2) > 0) {
                $configuredMids[] = $mid;
            }
        }
        $configuredMids = array_values(array_unique($configuredMids));
        rsort($configuredMids);
        $configuredMachineMap = $this->queryMachineBaseInfo($configuredMids);
        $deviceOptions = $this->buildDeviceOptions($configuredMids, $configuredMachineMap);

        $selectedMids = $this->normalizeMachineIdInput($ctx['m_id'] ?? '');
        $hasMachineFilter = $selectedMids !== [];
        if ($selectedMids !== []) {
            $selectedMids = array_values(array_intersect($selectedMids, $allowedMids));
        }

        $baseMids = $hasMachineFilter ? $selectedMids : $configuredMids;
        if ($baseMids !== []) {
            $baseMids = array_values(array_intersect($baseMids, $configuredMids));
        }

        if ($baseMids === []) {
            return [
                'state' => 200,
                'msg' => '查询成功',
                'data' => $this->emptyStatsPayload(
                    $months,
                    $ctx['m_id'] ?? '',
                    $deviceOptions,
                    $queryScope,
                    $updatedAt
                ),
            ];
        }

        $machineMap = [];
        $targetByMonthMap = [];
        foreach ($baseMids as $mid) {
            $machineMap[$mid] = $configuredMachineMap[$mid] ?? ['machine_id' => '', 'machine_name' => ''];
            $targetByMonthMap[$mid] = $allowedTargetByMonthMap[$mid] ?? [];
        }
        $targetMap = $this->sumTargetAmountByMonthMap($baseMids, $months, $targetByMonthMap);
        $fullChannelMap = $this->queryFullChannelAmountCostMap($baseMids);
        $currentMap = $statEnd >= $start
            ? $this->queryAmountCostMap($baseMids, $start, $statEnd)
            : [];
        $prevMap = $this->queryAmountCostMap($baseMids, $prevStart, $prevEnd);

        $list = [];
        $sumCurrentSale = 0;
        $sumCurrentCost = 0;
        $sumFullChannelAmount = 0;
        $sumFullChannelCost = 0;
        $sumCurrentTarget = 0;
        $sumPrevSale = 0;
        $sumPrevCost = 0;
        $summaryTargetByMonth = array_fill_keys($months, 0.0);
        $onTrackCount = 0;
        $weakestDevice = null;

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
            $costRate = $saleAmount > 0 ? round($costAmount / $saleAmount * 100, 2) : 0;
            $profitMargin = $saleAmount > 0 ? round($profitAmount / $saleAmount * 100, 2) : 0;
            $targetGapAmount = round(max($targetAmount - $saleAmount, 0), 2);
            $expectedPace = $this->calculateExpectedPace(
                $targetByMonthMap[$mid] ?? [],
                $months,
                $statEnd,
                $targetAmount
            );
            $paceDelta = $expectedPace === null
                ? null
                : round($achievementRate - $expectedPace, 2);
            $status = $paceDelta !== null && $paceDelta >= 0 ? 'ON_TRACK' : 'BEHIND';
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
                'target_configured' => true,
                'cost_rate' => $costRate,
                'profit_margin' => $profitMargin,
                'target_gap_amount' => $targetGapAmount,
                'expected_pace' => $expectedPace,
                'pace_delta' => $paceDelta,
                'status' => $status,
            ];

            $sumCurrentSale += $saleAmount;
            $sumCurrentCost += $costAmount;
            $sumFullChannelAmount += $fullChannelAmount;
            $sumFullChannelCost += $fullChannelCost;
            $sumCurrentTarget += $targetAmount;
            $sumPrevSale += $prevSale;
            $sumPrevCost += $prevCost;
            foreach ($months as $month) {
                $summaryTargetByMonth[$month] += (float) ($targetByMonthMap[$mid][$month] ?? 0);
            }

            if ($status === 'ON_TRACK') {
                $onTrackCount++;
            }
            if (
                $paceDelta !== null
                && ($weakestDevice === null || $paceDelta < (float) $weakestDevice['pace_delta'])
            ) {
                $weakestDevice = [
                    'm_id' => $mid,
                    'machine_name' => strval($machine['machine_name'] ?? ''),
                    'machine_id' => strval($machine['machine_id'] ?? ''),
                    'pace_delta' => $paceDelta,
                ];
            }
        }

        $sumCurrentSale = round($sumCurrentSale, 2);
        $sumCurrentCost = round($sumCurrentCost, 2);
        $sumFullChannelAmount = round($sumFullChannelAmount, 2);
        $sumFullChannelCost = round($sumFullChannelCost, 2);
        $sumCurrentTarget = round($sumCurrentTarget, 2);
        $sumCurrentProfit = round($sumCurrentSale - $sumCurrentCost, 2);
        $sumCurrentRate = $sumCurrentTarget > 0 ? round($sumCurrentSale / $sumCurrentTarget * 100, 2) : 0;
        $sumCostRate = $sumCurrentSale > 0 ? round($sumCurrentCost / $sumCurrentSale * 100, 2) : 0;
        $sumProfitMargin = $sumCurrentSale > 0 ? round($sumCurrentProfit / $sumCurrentSale * 100, 2) : 0;
        $sumTargetGap = round(max($sumCurrentTarget - $sumCurrentSale, 0), 2);
        $sumDailyTarget = $dayCount > 0 ? round($sumCurrentTarget / $dayCount, 2) : 0;
        $sumExpectedPace = $this->calculateExpectedPace(
            $summaryTargetByMonth,
            $months,
            $statEnd,
            $sumCurrentTarget
        );
        $sumPaceDelta = $sumExpectedPace === null
            ? null
            : round($sumCurrentRate - $sumExpectedPace, 2);
        $sumCurrentRatio = $sumFullChannelCost > 0 ? round($sumCurrentSale / $sumFullChannelCost, 2) : 0;
        $sumCurrentDays = $sumCurrentRatio > 0 ? round($dayCount / $sumCurrentRatio, 1) : 0;

        $sumPrevSale = round($sumPrevSale, 2);
        $sumPrevCost = round($sumPrevCost, 2);
        $sumPrevProfit = round($sumPrevSale - $sumPrevCost, 2);
        $sumPrevRate = $sumCurrentTarget > 0 ? round($sumPrevSale / $sumCurrentTarget * 100, 2) : 0;
        $sumPrevRatio = $sumFullChannelCost > 0 ? round($sumPrevSale / $sumFullChannelCost, 2) : 0;
        $sumPrevDays = $sumPrevRatio > 0 ? round($dayCount / $sumPrevRatio, 1) : 0;
        $saleCompare = $this->compare($sumCurrentSale, $sumPrevSale);
        $profitCompare = $this->compare($sumCurrentProfit, $sumPrevProfit);
        $queryScope = $this->buildQueryScope($parsed, $statEnd, count($list));
        $summary = [
            'full_channel_amount' => $sumFullChannelAmount,
            'full_channel_cost' => $sumFullChannelCost,
            'sale_amount' => $sumCurrentSale,
            'cost_amount' => $sumCurrentCost,
            'estimated_profit' => $sumCurrentProfit,
            'achievement_rate' => $sumCurrentRate,
            'turnover_ratio' => $sumCurrentRatio,
            'turnover_days' => $sumCurrentDays,
            'previous_sale_amount' => $sumPrevSale,
            'sale_growth_rate' => $saleCompare['rate'],
            'cost_rate' => $sumCostRate,
            'profit_amount' => $sumCurrentProfit,
            'previous_profit_amount' => $sumPrevProfit,
            'profit_margin' => $sumProfitMargin,
            'profit_growth_rate' => $profitCompare['rate'],
            'target_amount' => $sumCurrentTarget,
            'target_gap_amount' => $sumTargetGap,
            'daily_target_amount' => $sumDailyTarget,
            'expected_pace' => $sumExpectedPace,
            'pace_delta' => $sumPaceDelta,
        ];
        $summaryCompare = [
            'full_channel_amount' => $this->compare($sumFullChannelAmount, $sumFullChannelAmount),
            'full_channel_cost' => $this->compare($sumFullChannelCost, $sumFullChannelCost),
            'sale_amount' => $saleCompare,
            'cost_amount' => $this->compare($sumCurrentCost, $sumPrevCost),
            'estimated_profit' => $profitCompare,
            'achievement_rate' => $this->compare($sumCurrentRate, $sumPrevRate),
            'turnover_ratio' => $this->compare($sumCurrentRatio, $sumPrevRatio),
            'turnover_days' => $this->compare($sumCurrentDays, $sumPrevDays),
        ];
        $forecast = [];
        $trend = [];
        if ($includeDashboard) {
            $forecast = $this->buildForecast(
                $baseMids,
                $parsed,
                $queryScope,
                $summary,
                $statEnd
            );
            $trend = $this->buildTrend(
                $baseMids,
                $parsed,
                $summaryTargetByMonth,
                $statEnd,
                $prevEnd
            );
        }

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
                'query_scope' => $queryScope,
                'summary' => $summary,
                'summaryCompare' => $summaryCompare,
                'forecast' => $forecast,
                'device_progress' => [
                    'total_count' => count($list),
                    'on_track_count' => $onTrackCount,
                    'weakest_device' => $weakestDevice,
                ],
                'trend' => $trend,
                'updated_at' => $updatedAt,
                'list' => $list,
                'total' => count($list),
                'on_track_count' => $onTrackCount,
            ],
        ];
    }

    /**
     * @param int[] $mIds
     * @param array<int,array{machine_id:string,machine_name:string}> $machineMap
     * @return array<int,array<string,mixed>>
     */
    protected function buildDeviceOptions(array $mIds, array $machineMap): array
    {
        $list = [];
        foreach ($mIds as $mid) {
            $mid = intval($mid);
            if ($mid <= 0) {
                continue;
            }
            $machine = $machineMap[$mid] ?? ['machine_id' => '', 'machine_name' => ''];
            $machineId = strval($machine['machine_id'] ?? '');
            $machineName = strval($machine['machine_name'] ?? '');
            $list[] = [
                'm_id' => $mid,
                'machine_id' => $machineId,
                'machine_name' => $machineName,
                'label' => $machineId . ' ' . $machineName,
            ];
        }
        return $list;
    }

    /**
     * 当前月份尚未结束时，对比期最后一个月只统计到相同自然日和时间。
     *
     * @param array<string,mixed> $parsed
     */
    protected function resolveComparisonStatEnd(array $parsed, int $now = 0): int
    {
        $end = intval($parsed['end'] ?? 0);
        $prevEnd = intval($parsed['prevEnd'] ?? 0);
        $now = $now > 0 ? $now : time();
        if ($end <= 0 || $prevEnd <= 0 || date('Y-m', $end) !== date('Y-m', $now)) {
            return $prevEnd;
        }

        $previousMonth = date('Y-m', $prevEnd);
        $previousMonthDays = intval(date('t', strtotime($previousMonth . '-01')));
        $day = min(intval(date('j', $now)), $previousMonthDays);
        $aligned = strtotime(sprintf(
            '%s-%02d %s',
            $previousMonth,
            $day,
            date('H:i:s', $now)
        ));

        return $aligned === false ? $prevEnd : min(intval($aligned), $prevEnd);
    }

    /**
     * @param array<string,mixed> $parsed
     * @return array<string,mixed>
     */
    protected function buildQueryScope(array $parsed, int $statEnd, int $deviceCount): array
    {
        $months = is_array($parsed['months'] ?? null) ? $parsed['months'] : [];
        $start = intval($parsed['start'] ?? 0);
        $end = intval($parsed['end'] ?? 0);
        $prevStart = intval($parsed['prevStart'] ?? 0);
        $prevEnd = intval($parsed['prevEnd'] ?? 0);
        $totalDays = max(0, intval($parsed['dayCount'] ?? 0));

        if ($statEnd < $start) {
            $elapsedDays = 0;
        } elseif ($statEnd >= $end) {
            $elapsedDays = $totalDays;
        } else {
            $statDayStart = strtotime(date('Y-m-d 00:00:00', $statEnd));
            $elapsedDays = $statDayStart === false
                ? 0
                : intval(floor(($statDayStart - $start) / 86400) + 1);
            $elapsedDays = max(0, min($elapsedDays, $totalDays));
        }

        return [
            'start_month' => strval($months[0] ?? ''),
            'end_month' => strval($months === [] ? '' : $months[count($months) - 1]),
            'comparison_start_month' => $prevStart > 0 ? date('Y-m', $prevStart) : '',
            'comparison_end_month' => $prevEnd > 0 ? date('Y-m', $prevEnd) : '',
            'stat_end_at' => $statEnd > 0 ? date('Y-m-d H:i:s', $statEnd) : '',
            'total_days' => $totalDays,
            'elapsed_days' => $elapsedDays,
            'remaining_days' => max($totalDays - $elapsedDays, 0),
            'device_count' => max(0, $deviceCount),
        ];
    }

    /**
     * @param array<string,float|int> $targetByMonth
     * @param string[] $months
     */
    protected function calculatePlanAmount(
        array $targetByMonth,
        array $months,
        int $statEnd
    ): float {
        $planAmount = 0.0;
        foreach ($months as $month) {
            $month = strval($month);
            $targetAmount = round((float) ($targetByMonth[$month] ?? 0), 2);
            if ($targetAmount <= 0) {
                continue;
            }

            $bounds = $this->monthBounds($month);
            if ($statEnd < $bounds['start']) {
                continue;
            }
            if ($statEnd >= $bounds['end']) {
                $planAmount += $targetAmount;
                continue;
            }

            $statDayStart = strtotime(date('Y-m-d 00:00:00', $statEnd));
            $elapsedDays = $statDayStart === false
                ? 0
                : intval(floor(($statDayStart - $bounds['start']) / 86400) + 1);
            $monthDays = intval(date('t', $bounds['start']));
            if ($monthDays > 0 && $elapsedDays > 0) {
                $planAmount += $targetAmount * min($elapsedDays, $monthDays) / $monthDays;
            }
        }

        return round($planAmount, 2);
    }

    /**
     * @param array<string,float|int> $targetByMonth
     * @param string[] $months
     */
    protected function calculateExpectedPace(
        array $targetByMonth,
        array $months,
        int $statEnd,
        float $targetAmount
    ): ?float {
        if ($targetAmount <= 0) {
            return null;
        }
        $planAmount = $this->calculatePlanAmount($targetByMonth, $months, $statEnd);
        return round($planAmount / $targetAmount * 100, 2);
    }

    /**
     * @param int[] $mIds
     * @param array<string,mixed> $parsed
     * @param array<string,mixed> $queryScope
     * @param array<string,mixed> $summary
     * @return array<string,mixed>
     */
    protected function buildForecast(
        array $mIds,
        array $parsed,
        array $queryScope,
        array $summary,
        int $statEnd
    ): array {
        $targetAmount = round((float) ($summary['target_amount'] ?? 0), 2);
        $saleAmount = round((float) ($summary['sale_amount'] ?? 0), 2);
        $profitAmount = round((float) ($summary['profit_amount'] ?? 0), 2);
        $achievementRate = $targetAmount > 0
            ? round($saleAmount / $targetAmount * 100, 2)
            : null;
        $base = [
            'status' => 'UNAVAILABLE',
            'method' => 'RECENT_7_DAY_AVERAGE',
            'recent_daily_sale_amount' => null,
            'required_daily_sale_amount' => 0,
            'projected_sale_amount' => 0,
            'projected_achievement_rate' => null,
            'projected_target_delta_amount' => 0,
            'projected_profit_amount' => 0,
            'projected_incremental_profit_amount' => 0,
        ];
        if ($mIds === [] || $targetAmount <= 0) {
            return $base;
        }

        $end = intval($parsed['end'] ?? 0);
        if ($end > 0 && $statEnd >= $end) {
            $base['status'] = 'COMPLETED';
            $base['projected_sale_amount'] = $saleAmount;
            $base['projected_achievement_rate'] = $achievementRate;
            $base['projected_target_delta_amount'] = round($saleAmount - $targetAmount, 2);
            $base['projected_profit_amount'] = $profitAmount;
            return $base;
        }

        $months = is_array($parsed['months'] ?? null) ? $parsed['months'] : [];
        $endMonth = strval($months === [] ? '' : $months[count($months) - 1]);
        if ($endMonth !== date('Y-m') || $statEnd <= 0) {
            return $base;
        }

        $monthStart = strtotime($endMonth . '-01 00:00:00');
        $statDayStart = strtotime(date('Y-m-d 00:00:00', $statEnd));
        if ($monthStart === false || $statDayStart === false) {
            return $base;
        }

        $elapsedMonthDays = intval(floor(($statDayStart - $monthStart) / 86400) + 1);
        $recentDays = max(1, min(7, $elapsedMonthDays));
        $recentStart = strtotime('-' . ($recentDays - 1) . ' day', $statDayStart);
        if ($recentStart === false) {
            return $base;
        }

        $recentMap = $this->queryAmountCostTimelineMap($mIds, $recentStart, $statEnd, 'DAY');
        $recentSale = 0.0;
        foreach ($recentMap as $item) {
            $recentSale += (float) ($item['sale'] ?? 0);
        }
        $recentDailySale = round($recentSale / $recentDays, 2);
        $remainingDays = max(0, intval($queryScope['remaining_days'] ?? 0));
        $requiredDailySale = $remainingDays > 0
            ? round(max($targetAmount - $saleAmount, 0) / $remainingDays, 2)
            : 0;
        $projectedSale = round($saleAmount + $recentDailySale * $remainingDays, 2);
        $projectedAchievement = $targetAmount > 0
            ? round($projectedSale / $targetAmount * 100, 2)
            : null;
        $profitMargin = $saleAmount > 0 ? $profitAmount / $saleAmount : 0;
        $projectedProfit = round($projectedSale * $profitMargin, 2);

        return [
            'status' => 'IN_PROGRESS',
            'method' => 'RECENT_7_DAY_AVERAGE',
            'recent_daily_sale_amount' => $recentDailySale,
            'required_daily_sale_amount' => $requiredDailySale,
            'projected_sale_amount' => $projectedSale,
            'projected_achievement_rate' => $projectedAchievement,
            'projected_target_delta_amount' => round($projectedSale - $targetAmount, 2),
            'projected_profit_amount' => $projectedProfit,
            'projected_incremental_profit_amount' => round($projectedProfit - $profitAmount, 2),
        ];
    }

    /**
     * @param int[] $mIds
     * @param array<string,mixed> $parsed
     * @param array<string,float|int> $targetByMonth
     * @return array{granularity:string,items:array<int,array<string,mixed>>}
     */
    protected function buildTrend(
        array $mIds,
        array $parsed,
        array $targetByMonth,
        int $statEnd,
        int $prevEnd
    ): array {
        $months = is_array($parsed['months'] ?? null) ? $parsed['months'] : [];
        $start = intval($parsed['start'] ?? 0);
        $prevStart = intval($parsed['prevStart'] ?? 0);
        $granularity = count($months) === 1 ? 'DAY' : 'MONTH';
        if ($mIds === [] || $months === [] || $statEnd < $start) {
            return ['granularity' => $granularity, 'items' => []];
        }

        $currentPeriods = $this->buildTrendPeriods($months, $start, $statEnd, $granularity);
        $previousPeriods = $this->buildComparisonTrendPeriods(
            $prevStart,
            $prevEnd,
            $granularity,
            count($months)
        );
        $currentMap = $this->queryAmountCostTimelineMap($mIds, $start, $statEnd, $granularity);
        $previousMap = $this->queryAmountCostTimelineMap(
            $mIds,
            $prevStart,
            $prevEnd,
            $granularity
        );

        $previousCumulative = [];
        $previousSale = 0.0;
        $previousCost = 0.0;
        foreach ($previousPeriods as $period) {
            $previousSale += (float) ($previousMap[$period]['sale'] ?? 0);
            $previousCost += (float) ($previousMap[$period]['cost'] ?? 0);
            $previousCumulative[] = [
                'cost' => round($previousCost, 2),
                'profit' => round($previousSale - $previousCost, 2),
            ];
        }

        $items = [];
        $saleCumulative = 0.0;
        $costCumulative = 0.0;
        foreach ($currentPeriods as $index => $period) {
            $saleCumulative += (float) ($currentMap[$period]['sale'] ?? 0);
            $costCumulative += (float) ($currentMap[$period]['cost'] ?? 0);
            $profitCumulative = $saleCumulative - $costCumulative;
            $pointEnd = $this->trendPeriodEnd($period, $granularity, $statEnd);
            $planCumulative = $this->calculatePlanAmount(
                $targetByMonth,
                $months,
                $pointEnd
            );

            $periodMonth = substr($period, 0, 7);
            $targetCumulative = 0.0;
            foreach ($months as $month) {
                if ($month <= $periodMonth) {
                    $targetCumulative += (float) ($targetByMonth[$month] ?? 0);
                }
            }
            $achievementRate = $targetCumulative > 0
                ? round($saleCumulative / $targetCumulative * 100, 2)
                : null;
            $planRate = $targetCumulative > 0
                ? round($planCumulative / $targetCumulative * 100, 2)
                : null;

            $previousIndex = $previousCumulative === []
                ? null
                : min($index, count($previousCumulative) - 1);
            $previous = $previousIndex === null
                ? ['cost' => 0, 'profit' => 0]
                : $previousCumulative[$previousIndex];
            $items[] = [
                'period' => $period,
                'label' => $granularity === 'DAY'
                    ? date('m-d', strtotime($period))
                    : intval(substr($period, 5, 2)) . '月',
                'sale_cumulative_amount' => round($saleCumulative, 2),
                'cost_cumulative_amount' => round($costCumulative, 2),
                'profit_cumulative_amount' => round($profitCumulative, 2),
                'plan_cumulative_amount' => $planCumulative,
                'achievement_rate' => $achievementRate,
                'plan_rate' => $planRate,
                'previous_cost_cumulative_amount' => round((float) $previous['cost'], 2),
                'previous_profit_cumulative_amount' => round((float) $previous['profit'], 2),
            ];
        }

        return ['granularity' => $granularity, 'items' => $items];
    }

    /**
     * @param string[] $months
     * @return string[]
     */
    protected function buildTrendPeriods(
        array $months,
        int $start,
        int $statEnd,
        string $granularity
    ): array {
        if ($granularity === 'MONTH') {
            return array_values(array_filter($months, function ($month) use ($statEnd) {
                return $this->monthBounds(strval($month))['start'] <= $statEnd;
            }));
        }

        $periods = [];
        $cursor = $start;
        $lastDay = strtotime(date('Y-m-d 00:00:00', $statEnd));
        while ($lastDay !== false && $cursor <= $lastDay) {
            $periods[] = date('Y-m-d', $cursor);
            $next = strtotime('+1 day', $cursor);
            if ($next === false || $next <= $cursor) {
                break;
            }
            $cursor = $next;
        }
        return $periods;
    }

    /**
     * @return string[]
     */
    protected function buildComparisonTrendPeriods(
        int $start,
        int $end,
        string $granularity,
        int $monthCount
    ): array {
        if ($start <= 0 || $end <= 0 || $start > $end) {
            return [];
        }
        $periods = [];
        $cursor = $start;
        if ($granularity === 'MONTH') {
            for ($i = 0; $i < $monthCount; $i++) {
                $periods[] = date('Y-m', $cursor);
                $next = strtotime('+1 month', $cursor);
                if ($next === false || $next <= $cursor) {
                    break;
                }
                $cursor = $next;
            }
            return $periods;
        }

        $lastDay = strtotime(date('Y-m-d 00:00:00', $end));
        while ($lastDay !== false && $cursor <= $lastDay) {
            $periods[] = date('Y-m-d', $cursor);
            $next = strtotime('+1 day', $cursor);
            if ($next === false || $next <= $cursor) {
                break;
            }
            $cursor = $next;
        }
        return $periods;
    }

    protected function trendPeriodEnd(string $period, string $granularity, int $statEnd): int
    {
        if ($granularity === 'MONTH') {
            $periodEnd = $this->monthBounds($period)['end'];
        } else {
            $value = strtotime($period . ' 23:59:59');
            $periodEnd = $value === false ? $statEnd : intval($value);
        }
        return min($periodEnd, $statEnd);
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
        $effectiveMap = $this->queryEffectiveTargetAmountByMonthMap($mIds, $months);
        return $this->sumTargetAmountByMonthMap($mIds, $months, $effectiveMap);
    }

    /**
     * 月度目标记录按“记录存在”优先，即使金额为 0 也不会回退到默认目标。
     *
     * @param int[] $mIds
     * @param string[] $months
     * @return array<int,array<string,float>>
     */
    protected function queryEffectiveTargetAmountByMonthMap(array $mIds, array $months): array
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
        return $this->mergeEffectiveTargetAmountByMonthMap(
            $mIds,
            $months,
            $monthlyMap,
            $defaultMap
        );
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
        $effectiveMap = $this->mergeEffectiveTargetAmountByMonthMap(
            $mIds,
            $months,
            $monthlyMap,
            $defaultMap
        );
        return $this->sumTargetAmountByMonthMap($mIds, $months, $effectiveMap);
    }

    /**
     * @param int[] $mIds
     * @param string[] $months
     * @param array<int,array<string,float>> $monthlyMap
     * @param array<int,array<string,float>> $defaultMap
     * @return array<int,array<string,float>>
     */
    protected function mergeEffectiveTargetAmountByMonthMap(
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

            foreach ($months as $month) {
                $month = strval($month);
                // 月度目标按记录是否存在判断，存在时始终优先于默认目标。
                if (isset($monthlyMap[$mid]) && array_key_exists($month, $monthlyMap[$mid])) {
                    $map[$mid][$month] = round((float) $monthlyMap[$mid][$month], 2);
                    continue;
                }
                $map[$mid][$month] = round((float) ($defaultMap[$mid][$month] ?? 0), 2);
            }
        }

        return $map;
    }

    /**
     * @param int[] $mIds
     * @param string[] $months
     * @param array<int,array<string,float|int>> $effectiveMap
     * @return array<int,float>
     */
    protected function sumTargetAmountByMonthMap(
        array $mIds,
        array $months,
        array $effectiveMap
    ): array {
        $map = [];
        foreach ($mIds as $mid) {
            $mid = intval($mid);
            if ($mid <= 0) {
                continue;
            }
            $total = 0.0;
            foreach ($months as $month) {
                $total += (float) ($effectiveMap[$mid][strval($month)] ?? 0);
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
     * @param int[] $mIds
     * @return array<string,array{sale:float,cost:float}>
     */
    protected function queryAmountCostTimelineMap(
        array $mIds,
        int $start,
        int $end,
        string $granularity
    ): array {
        if ($mIds === [] || $start <= 0 || $end <= 0 || $start > $end) {
            return [];
        }

        $periodExpression = $granularity === 'DAY'
            ? "FROM_UNIXTIME(so.create_date, '%Y-%m-%d')"
            : "FROM_UNIXTIME(so.create_date, '%Y-%m')";
        $saleRows = Db::name('sale_orders')->alias('so')
            ->where('so.pay_status', 3)
            ->where('so.create_date', '>=', $start)
            ->where('so.create_date', '<=', $end)
            ->whereIn('so.m_id', $mIds)
            ->field($periodExpression . ' as period,'
                . ' IFNULL(SUM(so.total_price - so.refund_amount),0) as sale_amount')
            ->group('period')
            ->select()
            ->toArray();
        $costRows = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->where('so.pay_status', 3)
            ->where('so.create_date', '>=', $start)
            ->where('so.create_date', '<=', $end)
            ->whereIn('so.m_id', $mIds)
            ->field($periodExpression . ' as period,'
                . ' IFNULL(SUM((IFNULL(sod.success_quantity,0)'
                . ' - IFNULL(sod.refund_quantity,0))'
                . ' * IFNULL(sod.cost_price,0)),0) as cost_amount')
            ->group('period')
            ->select()
            ->toArray();

        $map = [];
        foreach ($saleRows as $row) {
            $period = strval($row['period'] ?? '');
            if ($period === '') {
                continue;
            }
            $map[$period] = [
                'sale' => round((float) ($row['sale_amount'] ?? 0), 2),
                'cost' => 0,
            ];
        }
        foreach ($costRows as $row) {
            $period = strval($row['period'] ?? '');
            if ($period === '') {
                continue;
            }
            if (!isset($map[$period])) {
                $map[$period] = ['sale' => 0, 'cost' => 0];
            }
            $map[$period]['cost'] = round((float) ($row['cost_amount'] ?? 0), 2);
        }
        ksort($map);
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
    protected function emptyStatsPayload(
        array $months,
        $rawMids,
        array $deviceOptions = [],
        array $queryScope = [],
        string $updatedAt = ''
    ): array
    {
        $selected = $this->normalizeMachineIdInput($rawMids);
        $updatedAt = $updatedAt !== '' ? $updatedAt : date('Y-m-d H:i:s');
        $queryScope['device_count'] = 0;
        return [
            'filters' => [
                'date' => implode('~', [$months[0], $months[count($months) - 1]]),
                'months' => $months,
                'm_id' => $selected,
            ],
            'machineOptions' => $deviceOptions,
            'query_scope' => $queryScope,
            'summary' => [
                'full_channel_amount' => 0,
                'full_channel_cost' => 0,
                'sale_amount' => 0,
                'cost_amount' => 0,
                'estimated_profit' => 0,
                'achievement_rate' => 0,
                'turnover_ratio' => 0,
                'turnover_days' => 0,
                'previous_sale_amount' => 0,
                'sale_growth_rate' => 0,
                'cost_rate' => 0,
                'profit_amount' => 0,
                'previous_profit_amount' => 0,
                'profit_margin' => 0,
                'profit_growth_rate' => 0,
                'target_amount' => 0,
                'target_gap_amount' => 0,
                'daily_target_amount' => 0,
                'expected_pace' => null,
                'pace_delta' => null,
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
            'forecast' => [
                'status' => 'UNAVAILABLE',
                'method' => 'RECENT_7_DAY_AVERAGE',
                'recent_daily_sale_amount' => null,
                'required_daily_sale_amount' => 0,
                'projected_sale_amount' => 0,
                'projected_achievement_rate' => null,
                'projected_target_delta_amount' => 0,
                'projected_profit_amount' => 0,
                'projected_incremental_profit_amount' => 0,
            ],
            'device_progress' => [
                'total_count' => 0,
                'on_track_count' => 0,
                'weakest_device' => null,
            ],
            'trend' => [
                'granularity' => count($months) === 1 ? 'DAY' : 'MONTH',
                'items' => [],
            ],
            'updated_at' => $updatedAt,
            'list' => [],
            'total' => 0,
            'on_track_count' => 0,
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
