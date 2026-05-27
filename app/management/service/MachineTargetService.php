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
     * @param array{m_id:mixed,date:mixed,price:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function add(array $ctx): array
    {
        return $this->saveInternal($ctx, false);
    }

    /**
     * @param array{id:mixed,m_id:mixed,date:mixed,price:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function update(array $ctx): array
    {
        $groupId = intval($ctx['id'] ?? 0);
        if ($groupId <= 0) {
            return ['state' => 100, 'msg' => 'id不能为空', 'data' => []];
        }
        $ctx['id'] = $groupId;
        return $this->saveInternal($ctx, true);
    }

    /**
     * @param array{id?:int,m_id:mixed,date:mixed,price:mixed} $ctx
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    protected function saveInternal(array $ctx, bool $isUpdate): array
    {
        $mIds = $this->normalizeMachineIdInput($ctx['m_id'] ?? '');
        if ($mIds === []) {
            return ['state' => 100, 'msg' => '设备id不能为空', 'data' => []];
        }

        $authWhere = is_array($ctx['auth_where'] ?? null) ? $ctx['auth_where'] : [];
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return ['state' => 100, 'msg' => '当前账号下没有可操作设备', 'data' => []];
        }
        $mIds = array_values(array_intersect($mIds, $allowedMids));
        if ($mIds === []) {
            return ['state' => 100, 'msg' => '设备不在当前账号可操作范围内', 'data' => []];
        }

        $monthPriceParse = $this->parseMonthPriceInput($ctx['date'] ?? '', $ctx['price'] ?? '');
        if (($monthPriceParse['state'] ?? 100) !== 200) {
            return ['state' => 100, 'msg' => (string) ($monthPriceParse['msg'] ?? '日期或金额格式错误'), 'data' => []];
        }

        $months = is_array($monthPriceParse['months'] ?? null) ? $monthPriceParse['months'] : [];
        $priceList = is_array($monthPriceParse['price_list'] ?? null) ? $monthPriceParse['price_list'] : [];
        $monthPriceMap = is_array($monthPriceParse['month_price_map'] ?? null) ? $monthPriceParse['month_price_map'] : [];
        $groupTargetAmount = round((float) array_sum($priceList), 2);

        if ($months === [] || $priceList === [] || $monthPriceMap === []) {
            return ['state' => 100, 'msg' => '日期或金额不能为空', 'data' => []];
        }

        sort($mIds);

        $groupId = max(0, intval($ctx['id'] ?? 0));
        Db::startTrans();
        try {
            if ($isUpdate) {
                if ($groupId <= 0) {
                    Db::rollback();
                    return ['state' => 100, 'msg' => 'id不能为空', 'data' => []];
                }
                $exist = Db::name('machine_target_group')->where('id', $groupId)->find();
                if (!$exist) {
                    Db::rollback();
                    return ['state' => 100, 'msg' => '目标配置不存在', 'data' => []];
                }

                Db::name('machine_target_group')->where('id', $groupId)->update([
                    'm_id' => implode(',', $mIds),
                    'months' => implode(',', $months),
                    'target_amount' => $groupTargetAmount,
                    'create_time' => time(),
                ]);

                Db::name('machine_target_monthly')->where('target_group_id', $groupId)->delete();
            } else {
                $groupId = intval(Db::name('machine_target_group')->insertGetId([
                    'm_id' => implode(',', $mIds),
                    'months' => implode(',', $months),
                    'target_amount' => $groupTargetAmount,
                    'create_time' => time(),
                ]));
            }

            $rows = [];
            foreach ($months as $month) {
                $bounds = $this->monthBounds($month);
                $monthPrice = round((float) ($monthPriceMap[$month] ?? 0), 2);
                foreach ($mIds as $mid) {
                    $rows[] = [
                        'target_group_id' => $groupId,
                        'm_id' => $mid,
                        'month' => $month,
                        'start_time' => $bounds['start'],
                        'end_time' => $bounds['end'],
                        'target_amount' => $monthPrice,
                    ];
                }
            }

            // 检查其他 group 是否已对相同 (m_id, month) 配置了目标，防止统计时重复累加
            if ($rows !== []) {
                $conflict = Db::name('machine_target_monthly')
                    ->whereIn('m_id', $mIds)
                    ->whereIn('month', $months)
                    ->where('target_group_id', '<>', $groupId)
                    ->value('m_id');
                if ($conflict) {
                    Db::rollback();
                    return ['state' => 100, 'msg' => '部分设备在所选月份已存在目标配置，请先删除已有配置再保存', 'data' => []];
                }
            }

            if ($rows !== []) {
                Db::name('machine_target_monthly')->insertAll($rows);
            }

            Db::commit();
            return [
                'state' => 200,
                'msg' => '保存成功',
                'data' => [
                    'id' => $groupId,
                    'm_id' => implode(',', $mIds),
                    'months' => $months,
                    'date' => implode(',', $months),
                    'price' => implode(',', array_map('strval', $priceList)),
                    'price_list' => $priceList,
                    'rows' => count($rows),
                ],
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return ['state' => 100, 'msg' => '保存失败：' . $e->getMessage(), 'data' => []];
        }
    }

    /**
     * @return array{state:int,msg:string,data:array<string,mixed>}
     */
    public function detail(int $id, array $authWhere = []): array
    {
        $row = Db::name('machine_target_group')->where('id', $id)->find();
        if (!$row) {
            return ['state' => 100, 'msg' => '目标配置不存在', 'data' => []];
        }

        $mIds = $this->normalizeMachineIdInput((string) ($row['m_id'] ?? ''));
        $allowedMids = $this->resolveAuthorizedMachineIds($authWhere);
        if ($allowedMids === []) {
            return ['state' => 100, 'msg' => '当前账号无权限查看该配置', 'data' => []];
        }
        $mIds = array_values(array_intersect($mIds, $allowedMids));
        if ($mIds === []) {
            return ['state' => 100, 'msg' => '当前账号无权限查看该配置', 'data' => []];
        }

        $months = $this->normalizeMonthList((string) ($row['months'] ?? ''));
        $monthPriceRows = Db::name('machine_target_monthly')
            ->where('target_group_id', intval($row['id'] ?? 0))
            ->field('month, IFNULL(MAX(target_amount),0) as target_amount')
            ->group('month')
            ->select()
            ->toArray();

        $monthPriceMap = [];
        foreach ($monthPriceRows as $item) {
            $month = strval($item['month'] ?? '');
            if ($month === '') {
                continue;
            }
            $monthPriceMap[$month] = round((float) ($item['target_amount'] ?? 0), 2);
        }

        $priceList = [];
        foreach ($months as $month) {
            if (isset($monthPriceMap[$month])) {
                $priceList[] = $monthPriceMap[$month];
                continue;
            }
            $priceList[] = round((float) ($row['target_amount'] ?? 0), 2);
        }

        $machineRows = [];
        if ($mIds !== []) {
            $machineRows = Db::name('machine')
                ->whereIn('m_id', $mIds)
                ->field('m_id,machine_id,machine_name')
                ->order('m_id', 'desc')
                ->select()
                ->toArray();
        }

        return [
            'state' => 200,
            'msg' => '查询成功',
            'data' => [
                'id' => intval($row['id'] ?? 0),
                'm_id' => implode(',', $mIds),
                'm_id_list' => $mIds,
                'date' => implode(',', $months),
                'months' => $months,
                'price' => implode(',', array_map('strval', $priceList)),
                'price_list' => $priceList,
                'create_time' => intval($row['create_time'] ?? 0),
                'machines' => $machineRows,
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

        $query = Db::name('machine_target_monthly')->alias('mt')
            ->join('machine m', 'm.m_id = mt.m_id', 'inner')
            ->whereIn('mt.month', $months)
            ->field('m.m_id,m.machine_id,m.machine_name')
            ->group('m.m_id,m.machine_id,m.machine_name')
            ->order('m.m_id', 'desc');

        $query->whereIn('mt.m_id', $allowedMids);

        $rows = $query->select()->toArray();
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
        $query = Db::name('machine')->where('vending_machine_type', 1);

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
            ->field('m_id, IFNULL(SUM(target_amount),0) as target_amount')
            ->group('m_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $mid = intval($row['m_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $map[$mid] = round((float) ($row['target_amount'] ?? 0), 2);
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
