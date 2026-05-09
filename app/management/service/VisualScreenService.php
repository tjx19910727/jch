<?php

namespace app\management\service;

use app\AppFactory\Management\Application;
use think\facade\Db;

/**
 * 数字大屏 /visual-screen 数据聚合（HTTP 与 WS 共用）
 */
class VisualScreenService
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
     * 省份视图下有效 m_id 列表；null 表示不按省份追加 m_id（仍走各 Client 内账号设备权限）
     * @return int[]|null
     */
    public function effectiveMachineIds(string $regionType, string $regionName): ?array
    {
        $regionName = trim($regionName);
        if ($regionName === '') {
            return null;
        }

        $provMids = $this->queryProvinceMIds($regionName);
        return array_values(array_unique(array_map('intval', $provMids)));
    }

    /**
     * 大屏设备统计范围：主柜 + 数据权限 / 省份 m_id
     *
     * @param int[]|null $mScope
     */
    protected function machineDashboardBaseQuery(?array $mScope)
    {
        $q = Db::name('machine')->where('vending_machine_type', 1);
        if ($mScope !== null) {
            $q->whereIn('m_id', $mScope);
        }
        return $q;
    }

    /**
     * total：主柜总台数；operating：在营(is_operating=1)；inStock：在库(is_operating=2)；online/offline：心跳在线
     *
     * @param int[]|null $mScope
     * @return array{total:int,operating:int,inStock:int,online:int,offline:int}
     */
    protected function buildMachineScreenCounts(?array $mScope): array
    {
        $row = $this->machineDashboardBaseQuery($mScope)
            ->fieldRaw(
                'COUNT(*) AS total'
                . ', SUM(CASE WHEN IFNULL(is_operating,0) = 1 THEN 1 ELSE 0 END) AS operating'
                . ', SUM(CASE WHEN IFNULL(is_operating,0) = 2 THEN 1 ELSE 0 END) AS in_stock'
                . ', SUM(CASE WHEN IFNULL(online,0) = 1 THEN 1 ELSE 0 END) AS online'
                . ', SUM(CASE WHEN IFNULL(online,0) = 2 THEN 1 ELSE 0 END) AS offline'
            )
            ->find();
        if (!$row) {
            return ['total' => 0, 'operating' => 0, 'inStock' => 0, 'online' => 0, 'offline' => 0];
        }
        $a = is_array($row) ? $row : $row->toArray();
        return [
            'total' => (int) ($a['total'] ?? 0),
            'operating' => (int) ($a['operating'] ?? 0),
            'inStock' => (int) ($a['in_stock'] ?? 0),
            'online' => (int) ($a['online'] ?? 0),
            'offline' => (int) ($a['offline'] ?? 0),
        ];
    }

    /**
     * @param array{regionType?:string,regionName?:string,cycle?:string,machinePage?:int,machinePageSize?:int,lastOrderId?:int} $ctx
     * @return array<string,mixed>
     */
    public function buildSnapshot(array $ctx): array
    {
        $regionType = $ctx['regionType'] ?? 'national';
        $regionName = trim((string) ($ctx['regionName'] ?? ''));
        $cycle = $ctx['cycle'] ?? 'day';
        $page = max(1, (int) ($ctx['machinePage'] ?? 1));
        $pageSize = min(256, max(1, (int) ($ctx['machinePageSize'] ?? 128)));
    $lastOrderId = max(0, (int) ($ctx['lastOrderId'] ?? 0));

        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        if (is_array($mScope) && $mScope === []) {
            return $this->emptySnapshot($regionType, $regionName, $cycle, $page, $pageSize);
        }

        $saleDataWhere = ['pay_status' => 3];
        if ($mScope !== null) {
            $saleDataWhere[] = ['m_id', 'in', $mScope];
        }
        $chartWhere = [];
        if ($mScope !== null) {
            $chartWhere[] = ['m_id', 'in', $mScope];
        }

        $saleData = $this->app->saleOrders->getData($saleDataWhere);
        $screenCounts = $this->buildMachineScreenCounts($mScope);
        $cargo = $this->app->machineChannel->getDataV2();

        $todayOrders = (int) ($saleData['today']['saleQuantity'] ?? 0);
        $yesterdayOrders = (int) ($saleData['yesterday']['saleQuantity'] ?? 0);
        $todaySales = (float) ($saleData['today']['saleMoney'] ?? 0);
        $yesterdaySales = (float) ($saleData['yesterday']['saleMoney'] ?? 0);
        $avgOrder = $todayOrders > 0 ? round($todaySales / $todayOrders, 2) : 0.0;

        // is_operating：1=在营 2=在库 3=外售（见数据库更新.sql）；仅统计主柜 vending_machine_type=1
        $deviceOverview = [
            'total' => (int) ($screenCounts['total'] ?? 0),
            'operating' => (int) ($screenCounts['operating'] ?? 0),
            'inStock' => (int) ($screenCounts['inStock'] ?? 0),
        ];

        $cargoStats = [
            'emptyChannels' => (int) ($cargo['empty'] ?? 0),
            'badChannels' => (int) ($cargo['bad'] ?? 0),
            'emptySlots' => (int) ($cargo['stockOut'] ?? 0),
        ];

        $tradeMetrics = [
            'todayOrderCount' => $todayOrders,
            'yesterdayOrderCount' => $yesterdayOrders,
            'todaySalesAmount' => $todaySales,
            'yesterdaySalesAmount' => $yesterdaySales,
            'averageOrderAmount' => $avgOrder,
        ];

        $chartType = $this->cycleToChartType($cycle);
        $chartResp = $this->app->saleOrders->getChartData($chartWhere, $chartType);
        $chartPayload = is_array($chartResp) ? $chartResp : obj2arr($chartResp);
        $chartRows = [];
        if (is_array($chartPayload) && array_key_exists('data', $chartPayload)) {
            $chartRows = $this->normalizeChartRows($chartPayload['data']);
        }

        return [
            'serverTime' => date('Y-m-d H:i:s'),
            'regionType' => $regionType,
            'regionName' => $regionName,
            'deviceOverview' => $deviceOverview,
            'cargoStats' => $cargoStats,
            'tradeMetrics' => $tradeMetrics,
            'productSalesShare' => $this->buildProductSalesShare($saleDataWhere, $cycle),
            'machineSalesShare' => $this->buildMachineSalesShare($saleDataWhere, $cycle),
            'deviceSalesRank' => $this->buildDeviceSalesRank($saleDataWhere, $cycle),
            'goodsPopularityRank' => $this->buildGoodsPopularityRank($saleDataWhere, $cycle),
            'mapValues' => $this->buildMapValues($regionType, $regionName, $mScope),
            'salesTrend' => [
                'cycle' => $cycle,
                'points' => $this->chartRowsToPoints($cycle, $chartRows),
            ],
            // 'machineList' => $this->buildMachineList($mScope, $page, $pageSize, $screenCounts),
            'realtimeOrders' => $this->buildRealtimeOrders($saleDataWhere, $regionType, $regionName, 30),
            'orderIncrement' => $this->buildOrderIncrement($saleDataWhere, $lastOrderId, $regionType, $regionName),
        ];
    }

    /**
     * @return array{cycle:string,points:array<int,array{label:string,value:float|int}>}
     */
    public function buildSalesTrend(array $ctx): array
    {
        $regionType = $ctx['regionType'] ?? 'national';
        $regionName = trim((string) ($ctx['regionName'] ?? ''));
        $cycle = $ctx['cycle'] ?? 'day';
        $debugEnabled = $this->shouldExposeAdcodeDebug();
        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        if (is_array($mScope) && $mScope === []) {
            $out = ['cycle' => $cycle, 'points' => []];
            if ($debugEnabled) {
                $out['debugTrend'] = [
                    'rowCount' => 0,
                    'rawSample' => null,
                ];
            }
            return $out;
        }
        $saleDataWhere = ['pay_status' => 3];
        if ($mScope !== null) {
            $saleDataWhere[] = ['m_id', 'in', $mScope];
        }
        $chartWhere = [];
        if ($mScope !== null) {
            $chartWhere[] = ['m_id', 'in', $mScope];
        }
        $chartType = $this->cycleToChartType($cycle);
        $chartResp = $this->app->saleOrders->getChartData($chartWhere, $chartType);
        $chartPayload = is_array($chartResp) ? $chartResp : obj2arr($chartResp);
        $chartRows = [];
        if (is_array($chartPayload) && array_key_exists('data', $chartPayload)) {
            $chartRows = $this->normalizeChartRows($chartPayload['data']);
        }
        $out = [
            'cycle' => $cycle,
            'points' => $this->chartRowsToPoints($cycle, $chartRows),
        ];
        if ($debugEnabled) {
            $out['debugTrend'] = [
                'rowCount' => is_array($chartRows) ? count($chartRows) : 0,
                'rawSample' => is_array($chartRows) && isset($chartRows[0]) && is_array($chartRows[0]) ? $chartRows[0] : null,
            ];
        }
        return $out;
    }

    /**
     * 独立设备列表接口：按区域返回设备列表与汇总
     *
     * @param array{regionType?:string,regionName?:string,page?:int,pageSize?:int,machinePage?:int,machinePageSize?:int} $ctx
     * @return array<string,mixed>
     */
    public function getMachineList(array $ctx): array
    {
        $regionType = $ctx['regionType'] ?? 'national';
        $regionName = trim((string) ($ctx['regionName'] ?? ''));
        $debugEnabled = $this->shouldExposeAdcodeDebug();
        $page = max(1, (int) ($ctx['page'] ?? ($ctx['machinePage'] ?? 1)));
        $pageSize = min(256, max(1, (int) ($ctx['pageSize'] ?? ($ctx['machinePageSize'] ?? 15))));

        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        if (is_array($mScope) && $mScope === []) {
            return [
                'summary' => ['total' => 0, 'online' => 0, 'offline' => 0],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => 0,
                'list' => [],
            ];
        }

        $screenCounts = $this->buildMachineScreenCounts($mScope);
        $out = $this->buildMachineList($mScope, $page, $pageSize, $screenCounts);
        if ($debugEnabled) {
            $out['debugPaging'] = [
                'resolvedPage' => (int) ($out['page'] ?? $page),
                'resolvedPageSize' => (int) ($out['pageSize'] ?? $pageSize),
                'rawPage' => $ctx['page'] ?? ($ctx['machinePage'] ?? null),
                'rawPageSize' => $ctx['pageSize'] ?? ($ctx['machinePageSize'] ?? null),
            ];
        }
        return $out;
    }

    protected function emptySnapshot(
        string $regionType,
        string $regionName,
        string $cycle,
        int $page,
        int $pageSize
    ): array {
        return [
            'serverTime' => date('Y-m-d H:i:s'),
            'regionType' => $regionType,
            'regionName' => $regionName,
            'deviceOverview' => ['total' => 0, 'operating' => 0, 'inStock' => 0],
            'cargoStats' => ['emptyChannels' => 0, 'badChannels' => 0, 'emptySlots' => 0],
            'tradeMetrics' => [
                'todayOrderCount' => 0,
                'yesterdayOrderCount' => 0,
                'todaySalesAmount' => 0.0,
                'yesterdaySalesAmount' => 0.0,
                'averageOrderAmount' => 0.0,
            ],
            'productSalesShare' => [],
            'machineSalesShare' => [],
            'deviceSalesRank' => [],
            'goodsPopularityRank' => [],
            'mapValues' => [
                'level' => $regionType === 'province' ? 'province' : 'national',
                'provinceName' => $regionName,
                'items' => [],
            ],
            'salesTrend' => ['cycle' => $cycle, 'points' => []],
            'machineList' => [
                'summary' => ['total' => 0, 'online' => 0, 'offline' => 0],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => 0,
                'list' => [],
            ],
            'realtimeOrders' => [],
            'orderIncrement' => [
                'regionType' => $regionType,
                'regionName' => $regionName,
                'fromOrderId' => 0,
                'toOrderId' => 0,
                'newOrderCount' => 0,
                'salesAmountDelta' => 0,
                'quantityDelta' => 0,
                'latestOrder' => null,
                'recentOrders' => [],
            ],
        ];
    }

    /**
     * @param array $saleWhere
     * @return array<string,mixed>
     */
    protected function buildOrderIncrement(array $saleWhere, int $lastOrderId, string $regionType, string $regionName): array
    {
        $where = $this->saleWhereToQuery($saleWhere);
        $query = Db::name('sale_orders')->alias('so')
            ->where($where)
            ->where('so.order_id', '>', $lastOrderId);

        $stat = (clone $query)
            ->fieldRaw('COUNT(*) as cnt, IFNULL(SUM(so.total_price),0) as amount, IFNULL(SUM(so.total_quantity),0) as qty, IFNULL(MAX(so.order_id),0) as latest_order_id')
            ->find();
        $stat = is_array($stat) ? $stat : (array) $stat;
        $newOrderCount = (int) ($stat['cnt'] ?? 0);

        $recentRows = [];
        if ($newOrderCount > 0) {
            $recentRows = (clone $query)
                ->field('so.order_id,so.trade_no,so.machine_id,so.machine_name,so.total_price,so.total_quantity,so.create_time')
                ->order('so.order_id', 'desc')
                ->limit(5)
                ->select()
                ->toArray();
        }

        $latestOrder = null;
        if (!empty($recentRows)) {
            $r = $recentRows[0];
            $latestOrder = [
                'orderId' => (int) ($r['order_id'] ?? 0),
                'tradeNo' => (string) ($r['trade_no'] ?? ''),
                'machineId' => (string) ($r['machine_id'] ?? ''),
                'machineName' => (string) ($r['machine_name'] ?? ''),
                'amount' => round((float) ($r['total_price'] ?? 0), 2),
                'quantity' => (int) ($r['total_quantity'] ?? 0),
                'time' => isset($r['create_time']) ? date('Y-m-d H:i:s', (int) $r['create_time']) : '',
            ];
        }

        $recentOrders = [];
        foreach ($recentRows as $r) {
            $recentOrders[] = [
                'orderId' => (int) ($r['order_id'] ?? 0),
                'tradeNo' => (string) ($r['trade_no'] ?? ''),
                'machineId' => (string) ($r['machine_id'] ?? ''),
                'machineName' => (string) ($r['machine_name'] ?? ''),
                'amount' => round((float) ($r['total_price'] ?? 0), 2),
                'quantity' => (int) ($r['total_quantity'] ?? 0),
                'time' => isset($r['create_time']) ? date('Y-m-d H:i:s', (int) $r['create_time']) : '',
            ];
        }

        return [
            'regionType' => $regionType,
            'regionName' => $regionName,
            'fromOrderId' => $lastOrderId,
            'toOrderId' => (int) ($stat['latest_order_id'] ?? 0),
            'newOrderCount' => $newOrderCount,
            'salesAmountDelta' => round((float) ($stat['amount'] ?? 0), 2),
            'quantityDelta' => (int) ($stat['qty'] ?? 0),
            'latestOrder' => $latestOrder,
            'recentOrders' => $recentOrders,
        ];
    }

    protected function cycleToChartType(string $cycle): int
    {
        if ($cycle === 'week') {
            return 2;
        }
        if ($cycle === 'month') {
            return 3;
        }
        return 1;
    }

    /**
     * 把 cycle 转为自义定查询起始时间戳（秒）
     * day => 当天 0 点
     * week => 最近 7 天
     * month => 最近 30 天
     */
    protected function cycleToSince(string $cycle, string $fallback = 'month'): ?int
    {
        $c = $cycle;
        if (!in_array($c, ['day', 'week', 'month'], true)) {
            $c = $fallback;
        }
        if ($c === 'day') {
            return strtotime(date('Y-m-d'));
        }
        if ($c === 'week') {
            return strtotime('-7 days');
        }
        if ($c === 'month') {
            return strtotime('-30 days');
        }
        return null;
    }

    /**
     * @param mixed $chartRows
     * @return array<int,array{label:string,value:float|int}>
     */
    protected function chartRowsToPoints(string $cycle, $chartRows): array
    {
        if (!is_array($chartRows)) {
            return [];
        }
        $points = [];
        foreach ($chartRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = '';
            if ($cycle === 'day') {
                $rawDate = $this->pickRowValue($row, ['countDate', 'countdate', 'create_date', 'createDate', 'date']);
                if ($rawDate !== null && $rawDate !== '') {
                    if (is_numeric($rawDate)) {
                        $label = date('m-d', (int) $rawDate);
                    } else {
                        $ts = strtotime((string) $rawDate);
                        if ($ts !== false) {
                            $label = date('m-d', $ts);
                        }
                    }
                }
            } elseif ($cycle === 'week') {
                $label = (string) ($this->pickRowValue($row, ['week', 'Week']) ?? '');
            } else {
                $label = (string) ($this->pickRowValue($row, ['month', 'Month']) ?? '');
            }
            $valRaw = $this->pickRowValue($row, ['totalPrice', 'totalprice', 'total_sale_price', 'totalSalePrice']);
            $val = $valRaw !== null ? (float) $valRaw : 0.0;
            if ($label !== '') {
                $points[] = ['label' => $label, 'value' => $val];
            }
        }
        return $points;
    }

    /**
     * @param mixed $raw
     * @return array<int,array<string,mixed>>
     */
    protected function normalizeChartRows($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_object($raw) && method_exists($raw, 'toArray')) {
            $arr = $raw->toArray();
            return is_array($arr) ? $arr : [];
        }
        return [];
    }

    /**
     * @param array<string,mixed> $row
     * @param string[] $keys
     * @return mixed|null
     */
    protected function pickRowValue(array $row, array $keys)
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row)) {
                return $row[$k];
            }
        }
        $lowerMap = [];
        foreach ($row as $k => $v) {
            $lowerMap[strtolower((string) $k)] = $v;
        }
        foreach ($keys as $k) {
            $lk = strtolower($k);
            if (array_key_exists($lk, $lowerMap)) {
                return $lowerMap[$lk];
            }
        }
        return null;
    }

    /**
     * @param array $saleWhere
     * @return array<int,array{label:string,value:int,percent:int}>
     */
    protected function buildProductSalesShare(array $saleWhere, string $cycle = 'month'): array
    {
        $since = $this->cycleToSince($cycle);
        $where = $this->saleWhereToQuery($saleWhere);
        $where[] = ['sod.g_id', '>', 0];
        $rows = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->where($where)
            ->when($since, function ($query) use ($since) {
                $query->where('so.create_date', '>=', $since);
            })
            ->field('sod.g_name as label, IFNULL(SUM(sod.quantity - sod.refund_quantity),0) as v')
            ->group('sod.g_id,sod.g_name')
            ->order('v', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) $r['v'];
        }
        $out = [];
        foreach ($rows as $r) {
            $v = (int) round((float) $r['v']);
            $pct = $total > 0 ? (int) round(((float) $r['v'] / $total) * 100) : 0;
            $out[] = ['label' => (string) $r['label'], 'value' => $v, 'percent' => $pct];
        }
        return $out;
    }

    /**
     * @param array $saleWhere
     * @return array<int,array{label:string,value:int,percent:int}>
     */
    protected function buildMachineSalesShare(array $saleWhere, string $cycle = 'month'): array
    {
        $since = $this->cycleToSince($cycle);
        $where = $this->saleWhereToQuery($saleWhere);
        $rows = Db::name('sale_orders')->alias('so')
            ->where($where)
            ->when($since, function ($query) use ($since) {
                $query->where('so.create_date', '>=', $since);
            })
            ->field('so.machine_name as label, IFNULL(SUM(so.total_quantity),0) as v')
            ->group('so.m_id,so.machine_name')
            ->order('v', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) $r['v'];
        }
        $out = [];
        foreach ($rows as $r) {
            $v = (int) round((float) $r['v']);
            $pct = $total > 0 ? (int) round(((float) $r['v'] / $total) * 100) : 0;
            $out[] = ['label' => (string) $r['label'], 'value' => $v, 'percent' => $pct];
        }
        return $out;
    }

    /**
     * @param array $saleWhere
     * @return array<int,array{name:string,value:float|int}>
     */
    protected function buildDeviceSalesRank(array $saleWhere, string $cycle = 'week'): array
    {
        $since = $this->cycleToSince($cycle, 'week');
        $where = $this->saleWhereToQuery($saleWhere);
        $rows = Db::name('sale_orders')->alias('so')
            ->where($where)
            ->when($since, function ($query) use ($since) {
                $query->where('so.create_date', '>=', $since);
            })
            ->field('so.machine_name as name, IFNULL(SUM(so.total_price),0) as value')
            ->group('so.m_id,so.machine_name')
            ->order('value', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['name' => (string) $r['name'], 'value' => round((float) $r['value'], 2)];
        }
        return $out;
    }

    /**
     * @param array $saleWhere
    * @return array<int,array{name:string,value:int,category:string,rebuyRate:int}>
     */
    protected function buildGoodsPopularityRank(array $saleWhere, string $cycle = 'month'): array
    {
        $since = $this->cycleToSince($cycle);
        $where = $this->saleWhereToQuery($saleWhere);
        $where[] = ['sod.g_id', '>', 0];
        $rows = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->join('goods g', 'g.g_id = sod.g_id', 'left')
            ->join('goods_category gc', 'gc.gc_id = g.gc_id', 'left')
            ->where($where)
            ->when($since, function ($query) use ($since) {
                $query->where('so.create_date', '>=', $since);
            })
            ->field('sod.g_id,sod.g_name as name, sod.retail_price,IFNULL(SUM(sod.quantity - sod.refund_quantity),0) as value, IFNULL(gc.gc_name,"") as category')
            ->group('sod.g_id,sod.g_name,gc.gc_name')
            ->order('value', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $gIds = [];
        foreach ($rows as $r) {
            if (!empty($r['g_id'])) {
                $gIds[] = (int) $r['g_id'];
            }
        }
        // $rebuyMap = $this->fetchRebuyRatesForGids($gIds, $since30, $saleWhere);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'name' => (string) $r['name'],
                'value' => (int) round((float) ($r['value']), 0),
                'category' => (string) ($r['category'] ?? ''),
                'retail_price' => (float) ($r['retail_price']),
            ];
        }
        return $out;
    }

    /**
     * 复购率：按「购买主体」统计。user_id>0 用用户；否则用设备 m_id 代表匿名客流（同机多次购算复购）。
     * 统计窗口与人气榜销量一致（与入参 saleWhere 中的 create_date 下限一致，默认近 30 天）。
     *
     * @param int[] $gIds
     * @param array $saleWhere 与 buildGoodsPopularityRank 相同结构的订单条件
     * @return array<int,int> g_id => 0–100
     */
    protected function fetchRebuyRatesForGids(array $gIds, int $sinceDate, array $saleWhere): array
    {
        $gIds = array_values(array_unique(array_filter($gIds)));
        if ($gIds === []) {
            return [];
        }
        $q = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'inner')
            ->where('so.pay_status', '=', 3)
            ->where('so.create_date', '>=', $sinceDate)
            ->whereIn('sod.g_id', $gIds);
        foreach ($this->saleWhereToQuery($saleWhere) as $cond) {
            if (!is_array($cond) || count($cond) < 3) {
                continue;
            }
            $q->where($cond[0], $cond[1], $cond[2]);
        }
        $buyerExpr = 'IF(so.user_id > 0, CONCAT(\'u\', so.user_id), CONCAT(\'m\', so.m_id))';
        $rows = $q->fieldRaw('sod.g_id, ' . $buyerExpr . ' AS buyer_key, COUNT(DISTINCT so.order_id) AS order_cnt')
            ->group('sod.g_id, IF(so.user_id > 0, CONCAT(\'u\', so.user_id), CONCAT(\'m\', so.m_id))')
            ->select()
            ->toArray();
        $byGid = [];
        foreach ($rows as $r) {
            $gid = (int) ($r['g_id'] ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $cnt = (int) ($r['order_cnt'] ?? 0);
            if (!isset($byGid[$gid])) {
                $byGid[$gid] = ['buyers' => 0, 'repeat' => 0];
            }
            $byGid[$gid]['buyers']++;
            if ($cnt >= 2) {
                $byGid[$gid]['repeat']++;
            }
        }
        $rates = [];
        foreach ($byGid as $gid => $stat) {
            $b = (int) $stat['buyers'];
            $rates[$gid] = $b > 0 ? (int) min(100, max(0, (int) round(100 * ((int) $stat['repeat']) / $b))) : 0;
        }
        return $rates;
    }

    /**
     * @param int[]|null $mScope
    * @return array{level:string,provinceName:string,items:array<int,array{name:string,value:int,quantity:int,adcode:string}>}
     */
    protected function buildMapValues(string $regionType, string $regionName, ?array $mScope): array
    {
        if ($regionType === 'province' && $regionName !== '') {
            $mids = $mScope;
            if ($mids === null) {
                $mids = $this->queryProvinceMIds($regionName);
            }
            $items = $this->aggregateSalesByCity($mids);
            return [
                'level' => 'province',
                'provinceName' => $regionName,
                'items' => $items,
            ];
        }
        $items = $this->aggregateSalesByProvince($mScope);
        return [
            'level' => 'national',
            'provinceName' => '',
            'items' => $items,
        ];
    }

    /**
     * @param int[]|null $mScope
    * @return array<int,array{name:string,value:int,quantity:int,adcode:string}>
     */
    protected function aggregateSalesByProvince(?array $mScope): array
    {
        $debugEnabled = $this->shouldExposeAdcodeDebug();
        $q = Db::name('sale_orders')->alias('so')
            ->join('machine m', 'm.m_id = so.m_id', 'left')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->where('so.pay_status', '=', 3)
            ->where('so.create_date', '>=', strtotime('-30 days'))
            ->field('IFNULL(s.cname,"") as name, IFNULL(s.code_full,s.code) as adcode, COUNT(DISTINCT so.m_id) as device_count, IFNULL(SUM(so.total_quantity),0) as quantity')
            ->group('m.state_id,s.cname,s.code,s.code_full')
            ->order('quantity', 'desc')
            ->limit(40);
        if ($mScope !== null) {
            $q->whereIn('so.m_id', $mScope);
        }
        $rows = $q->select()->toArray();
        $out = [];
        foreach ($rows as $r) {
            if ($r['name'] === '') {
                continue;
            }
            $rawAdcode = (string) ($r['adcode'] ?? '');
            $adcode = $this->normalizeGbAdcode((string) ($r['adcode'] ?? ''));
            $item = [
                'name' => (string) $r['name'],
                'value' => (int) ($r['device_count'] ?? 0),
                'quantity' => (int) round((float) ($r['quantity'] ?? 0)),
                'adcode' => $adcode,
            ];
            if ($debugEnabled) {
                $item['debugAdcode'] = [
                    'raw' => $rawAdcode,
                    'stateRaw' => '',
                    'normalized' => $adcode,
                ];
            }
            $out[] = $item;
        }

        $zeroItems = $this->buildProvinceZeroItems($mScope, $debugEnabled);
        if ($zeroItems !== []) {
            $exists = [];
            $deviceByName = [];
            foreach ($zeroItems as $it) {
                $name = (string) ($it['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $deviceByName[$name] = (int) ($it['value'] ?? 0);
            }
            foreach ($out as $idx => $it) {
                $name = (string) ($it['name'] ?? '');
                if ($name !== '') {
                    $exists[$name] = true;
                    if (isset($deviceByName[$name])) {
                        $out[$idx]['value'] = (int) $deviceByName[$name];
                    }
                }
            }
            foreach ($zeroItems as $it) {
                $name = (string) ($it['name'] ?? '');
                if ($name === '' || isset($exists[$name])) {
                    continue;
                }
                $out[] = $it;
            }
        }

        usort($out, static function (array $a, array $b): int {
            return (int) ($b['value'] ?? 0) <=> (int) ($a['value'] ?? 0);
        });
        return array_slice($out, 0, 40);
    }

    /**
     * national 视图兜底：有设备但近30天无支付订单的省份也返回 value=0
     *
     * @param int[]|null $mScope
     * @return array<int,array{name:string,value:int,quantity:int,adcode:string}>
     */
    protected function buildProvinceZeroItems(?array $mScope, bool $debugEnabled = false): array
    {
        $q = Db::name('machine')->alias('m')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->where('m.vending_machine_type', 1)
            ->field('IFNULL(s.cname,"") as name, IFNULL(s.code_full,s.code) as adcode, COUNT(DISTINCT m.m_id) as device_count')
            ->group('m.state_id,s.cname,s.code,s.code_full')
            ->order('m.state_id', 'asc')
            ->limit(60);
        if ($mScope !== null) {
            if ($mScope === []) {
                return [];
            }
            $q->whereIn('m.m_id', $mScope);
        }
        $rows = $q->select()->toArray();
        $out = [];
        foreach ($rows as $r) {
            if (($r['name'] ?? '') === '') {
                continue;
            }
            $rawAdcode = (string) ($r['adcode'] ?? '');
            $adcode = $this->normalizeGbAdcode($rawAdcode);
            $item = [
                'name' => (string) $r['name'],
                'value' => (int) ($r['device_count'] ?? 0),
                'quantity' => 0,
                'adcode' => $adcode,
            ];
            if ($debugEnabled) {
                $item['debugAdcode'] = [
                    'raw' => $rawAdcode,
                    'stateRaw' => '',
                    'normalized' => $adcode,
                ];
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * @param int[] $mids
    * @return array<int,array{name:string,value:int,quantity:int,adcode:string}>
     */
    protected function aggregateSalesByCity(array $mids): array
    {
        $debugEnabled = $this->shouldExposeAdcodeDebug();
        if ($mids === []) {
            return [];
        }
        $rows = Db::name('sale_orders')->alias('so')
            ->join('machine m', 'm.m_id = so.m_id', 'left')
            ->join('earth_cities c', 'm.city_id = c.id', 'left')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->where('so.pay_status', '=', 3)
            ->whereIn('so.m_id', $mids)
            ->where('so.create_date', '>=', strtotime('-30 days'))
            ->field('IFNULL(c.cname,"") as name, IFNULL(c.code_full,c.code) as adcode, IFNULL(s.code_full,s.code) as state_adcode, COUNT(DISTINCT so.m_id) as device_count, IFNULL(SUM(so.total_quantity),0) as quantity')
            ->group('m.city_id,c.cname,c.code,c.code_full,s.code,s.code_full')
            ->order('quantity', 'desc')
            ->limit(40)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            if ($r['name'] === '') {
                continue;
            }
            $rawAdcode = (string) ($r['adcode'] ?? '');
            $stateRawAdcode = (string) ($r['state_adcode'] ?? '');
            $adcode = $this->normalizeGbAdcode(
                $rawAdcode,
                $stateRawAdcode
            );
            $item = [
                'name' => (string) $r['name'],
                'value' => (int) ($r['device_count'] ?? 0),
                'quantity' => (int) round((float) ($r['quantity'] ?? 0)),
                'adcode' => $adcode,
            ];
            if ($debugEnabled) {
                $item['debugAdcode'] = [
                    'raw' => $rawAdcode,
                    'stateRaw' => $stateRawAdcode,
                    'normalized' => $adcode,
                ];
            }
            $out[] = $item;
        }
        $zeroItems = $this->buildCityZeroItems($mids, $debugEnabled);
        if ($zeroItems !== []) {
            $exists = [];
            $deviceByAdcode = [];
            foreach ($zeroItems as $it) {
                $k = (string) ($it['adcode'] ?? '');
                if ($k === '') {
                    continue;
                }
                $deviceByAdcode[$k] = (int) ($it['value'] ?? 0);
            }
            foreach ($out as $idx => $it) {
                $k = (string) ($it['adcode'] ?? '');
                if ($k !== '') {
                    $exists[$k] = true;
                    if (isset($deviceByAdcode[$k])) {
                        $out[$idx]['value'] = (int) $deviceByAdcode[$k];
                    }
                }
            }
            foreach ($zeroItems as $it) {
                $k = (string) ($it['adcode'] ?? '');
                if ($k === '' || isset($exists[$k])) {
                    continue;
                }
                $out[] = $it;
            }
        }
        usort($out, static function (array $a, array $b): int {
            return (int) ($b['value'] ?? 0) <=> (int) ($a['value'] ?? 0);
        });
        $out = array_slice($out, 0, 40);
        if ($out === []) {
            return $this->buildCityZeroItems($mids, $debugEnabled);
        }
        return $out;
    }

    /**
     * 省份视图兜底：该省设备有数据权限但近30天无支付订单时，返回城市列表（value=0）
     *
     * @param int[] $mids
     * @return array<int,array{name:string,value:int,quantity:int,adcode:string}>
     */
    protected function buildCityZeroItems(array $mids, bool $debugEnabled = false): array
    {
        if ($mids === []) {
            return [];
        }
        $rows = Db::name('machine')->alias('m')
            ->join('earth_cities c', 'm.city_id = c.id', 'left')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->whereIn('m.m_id', $mids)
            ->field('IFNULL(c.cname,"") as name, IFNULL(c.code_full,c.code) as adcode, IFNULL(s.code_full,s.code) as state_adcode, COUNT(DISTINCT m.m_id) as device_count')
            ->group('m.city_id,c.cname,c.code,c.code_full,s.code,s.code_full')
            ->order('m.city_id', 'asc')
            ->limit(40)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            if (($r['name'] ?? '') === '') {
                continue;
            }
            $rawAdcode = (string) ($r['adcode'] ?? '');
            $stateRawAdcode = (string) ($r['state_adcode'] ?? '');
            $adcode = $this->normalizeGbAdcode($rawAdcode, $stateRawAdcode);
            $item = [
                'name' => (string) $r['name'],
                'value' => (int) ($r['device_count'] ?? 0),
                'quantity' => 0,
                'adcode' => $adcode,
            ];
            if ($debugEnabled) {
                $item['debugAdcode'] = [
                    'raw' => $rawAdcode,
                    'stateRaw' => $stateRawAdcode,
                    'normalized' => $adcode,
                ];
            }
            $out[] = $item;
        }
        return $out;
    }

    protected function shouldExposeAdcodeDebug(): bool
    {
        if (function_exists('env')) {
            return (bool) \env('app_debug', false);
        }
        $val = getenv('APP_DEBUG');
        if ($val === false) {
            return false;
        }
        return in_array(strtolower((string) $val), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * 归一化为国标 6 位 adcode（仅数字）：
     * - 2 位省码 => XX0000
     * - 4 位地市码 => XXXX00
     * - 6 位直接使用
     * - 1~2 位市码 + 2 位省码 => 省码 + 市码(左补0) + 00（兼容直辖市）
     */
    protected function normalizeGbAdcode(string $adcodeRaw, string $stateAdcodeRaw = ''): string
    {
        $adcode = preg_replace('/\D/', '', $adcodeRaw);
        $stateAdcode = preg_replace('/\D/', '', $stateAdcodeRaw);

        if ($adcode === null || $stateAdcode === null) {
            return '000000';
        }

        $state2 = '';
        $state1 = '';
        $city2 = '';
        if (strlen($stateAdcode) >= 1) {
            $state1 = substr($stateAdcode, -1);
        }
        if (strlen($stateAdcode) >= 2) {
            $state2 = substr($stateAdcode, -2);
        }
        if (strlen($adcode) >= 2) {
            $city2 = substr($adcode, -2);
        }
        $adcodeLen = strlen($adcode);
        if ($adcodeLen >= 6) {
            $six = substr($adcode, 0, 6);
            if ($six[0] !== '0') {
                return $six;
            }
            if ($state2 !== '' && $city2 !== '') {
                return $state2 . $city2 . '00';
            }
            if ($state1 !== '' && $state1 !== '0') {
                return $state1 . substr($six, 1, 5);
            }
            return $six;
        }

        // 省份编码兜底：如 CHN044 -> 044，应按末两位归一为 440000
        if ($stateAdcode === '' && $adcodeLen === 3) {
            return substr($adcode, -2) . '0000';
        }

        if ($adcodeLen === 4) {
            return $adcode . '00';
        }

        if ($adcodeLen >= 1 && $adcodeLen <= 2) {
            if ($state2 !== '') {
                $cityPart = str_pad(substr($adcode, 0, 2), 2, '0', STR_PAD_LEFT);
                return $state2 . $cityPart . '00';
            }
        }

        if ($adcodeLen === 2) {
            return $adcode . '0000';
        }

        if ($adcodeLen > 0 && $adcodeLen < 6) {
            return str_pad($adcode, 6, '0', STR_PAD_RIGHT);
        }

        return '000000';
    }

    /**
     * @param int[]|null $mScope
     * @param array{total:int,operating:int,inStock:int,online:int,offline:int} $screenCounts
     * @return array<string,mixed>
     */
    protected function buildMachineList(?array $mScope, int $page, int $pageSize, array $screenCounts): array
    {
        $page = max(1, (int) $page);
        $pageSize = min(256, max(1, (int) $pageSize));
        $offset = ($page - 1) * $pageSize;

        $q = Db::name('machine')->alias('m')
            ->join('machine_on_off moo', 'moo.m_id = m.m_id', 'left')
            ->where('m.vending_machine_type', 1)
            ->field('m.m_id,m.machine_id,m.machine_name,m.online,m.street,moo.on_off_machine,moo.on_off_ckc');
        if ($mScope !== null) {
            $q->whereIn('m.m_id', $mScope);
        }
        $total = (int) (clone $q)->count();
        $rows = $q->order('m.m_id', 'desc')
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();
        $mIds = array_column($rows, 'm_id');
        $salesMap = $this->todaySalesByMids($mIds);
    // 频道统计与 on-off 原始字段
    $chStats = $this->channelStatsForMids($mIds);
    // 保留原始 on_off 字段，不在此解析
    $onOffMap = [];
    $list = [];
        foreach ($rows as $m) {
            $mid = (int) $m['m_id'];
            $st = $chStats[$mid] ?? ['emptyChannels' => 0, 'badChannels' => 0, 'emptySlots' => 0];
            $onOff = $onOffMap[$mid] ?? ['businessHours' => '', 'powerOnTime' => '', 'powerOffTime' => ''];
            $list[] = [
                'id' => (string) ($m['machine_id'] ?? ''),
                'name' => (string) ($m['machine_name'] ?? ''),
                'online' => (int) ($m['online'] ?? 0) === 1,
                'businessHours' => (string) ($onOff['businessHours'] ?? ''),
                'on_off_machine' => $m['on_off_machine'] ?? '',
                'on_off_ckc' =>  $m['on_off_ckc'] ?? '',
                'address' => (string) ($m['street'] ?? ''),
                'sales' => (int) ($salesMap[$mid] ?? 0),
                'emptyChannels' => (int) $st['emptyChannels'],
                'badChannels' => (int) $st['badChannels'],
                'emptySlots' => (int) $st['emptySlots'],
            ];
        }
        return [
            'summary' => [
                'total' => (int) ($screenCounts['total'] ?? $total),
                'online' => (int) ($screenCounts['online'] ?? 0),
                'offline' => (int) ($screenCounts['offline'] ?? 0),
            ],
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'list' => $list,
        ];
    }

    /**
     * @param int[] $mIds
     * @return array<int,array{emptyChannels:int,badChannels:int,emptySlots:int}>
     */
    protected function channelStatsForMids(array $mIds): array
    {
        if ($mIds === []) {
            return [];
        }
        $rows = Db::name('machine_channel')
            ->whereIn('m_id', $mIds)
            ->field('m_id,
                SUM(CASE WHEN g_id = 0 THEN 1 ELSE 0 END) as emptyChannels,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as badChannels,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as emptySlots')
            ->group('m_id')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['m_id']] = [
                'emptyChannels' => (int) $r['emptyChannels'],
                'badChannels' => (int) $r['badChannels'],
                'emptySlots' => (int) $r['emptySlots'],
            ];
        }
        return $map;
    }

    /**
     * @param int[] $mIds
     * @return array<int,float|int>
     */
    protected function todaySalesByMids(array $mIds): array
    {
        if ($mIds === []) {
            return [];
        }
        $start = strtotime(date('Y-m-d'));
        $rows = Db::name('sale_orders')
            ->where('pay_status', 3)
            ->where('create_date', '>=', $start)
            ->whereIn('m_id', $mIds)
            ->field('m_id, IFNULL(SUM(total_price),0) as v')
            ->group('m_id')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['m_id']] = round((float) $r['v'], 2);
        }
        return $map;
    }

    /**
     * @param array $saleWhere
     * @return array<int,array<string,mixed>>
     */
    protected function buildRealtimeOrders(array $saleWhere, string $regionType, string $regionName, int $limit): array
    {
        $where = $this->saleWhereToQuery($saleWhere);
        $rows = Db::name('sale_orders')->alias('so')
            ->join('machine m', 'm.m_id = so.m_id', 'left')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->where($where)
            ->field('so.trade_no,so.machine_name,so.create_time,so.total_price,s.cname as state_name,(SELECT sod.g_name FROM sale_orders_details sod WHERE sod.order_id = so.order_id LIMIT 1) as g_name, m.lng,m.lat')
            ->order('so.order_id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (string) ($r['trade_no'] ?? ''),
                'machine' => (string) ($r['machine_name'] ?? ''),
                'product' => (string) ($r['g_name'] ?? ''),
                'time' => isset($r['create_time']) ? date('H:i:s', (int) $r['create_time']) : '',
                'amount' => round((float) ($r['total_price'] ?? 0), 2),
                'status' => '已支付',
                'regionType' => $regionType === 'province' ? 'province' : 'national',
                'regionName' => $regionType === 'province' ? $regionName : (string) ($r['state_name'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @param array<int,mixed> $saleWhere
     * @return array<int,mixed>
     */
    protected function saleWhereToQuery(array $saleWhere): array
    {
        $out = [];
        foreach ($saleWhere as $k => $cond) {
            if (is_string($k) && $k !== '0' && !is_numeric($k)) {
                $out[] = ['so.' . $k, '=', $cond];
                continue;
            }
            if (!is_array($cond) || count($cond) < 3) {
                continue;
            }
            [$col, $op, $val] = $cond;
            if ($col === 'pay_status') {
                $out[] = ['so.pay_status', $op, $val];
            } elseif ($col === 'm_id') {
                $out[] = ['so.m_id', $op, $val];
            } else {
                $out[] = ['so.' . $col, $op, $val];
            }
        }
        return $out;
    }

    /**
     * @return int[]
     */
    protected function queryProvinceMIds(string $regionName): array
    {
        $raw = trim($regionName);
        if ($raw === '') {
            return [];
        }

        $norm = preg_replace('/(省|市|壮族自治区|回族自治区|维吾尔自治区|自治区)$/', '', $raw);
        $norm = is_string($norm) ? trim($norm) : '';
        $names = array_values(array_unique(array_filter([$raw, $norm], static function ($v): bool {
            return is_string($v) && $v !== '';
        })));

        $stateIds = [];
        $cityIds = [];

        // 1) 先精确匹配省/市名称
        foreach ($names as $name) {
            $stateIds = array_merge(
                $stateIds,
                Db::name('earth_states')->where('cname', '=', $name)->column('id'),
                Db::name('earth_states')->where('name', '=', $name)->column('id')
            );
            $cityIds = array_merge(
                $cityIds,
                Db::name('earth_cities')->where('cname', '=', $name)->column('id'),
                Db::name('earth_cities')->where('name', '=', $name)->column('id')
            );
        }

        // 2) 精确未命中时再模糊匹配
        if ($stateIds === [] && $cityIds === []) {
            foreach ($names as $name) {
                $like = '%' . $name . '%';
                $stateIds = array_merge(
                    $stateIds,
                    Db::name('earth_states')->whereRaw('(cname LIKE ? OR name LIKE ?)', [$like, $like])->column('id')
                );
                $cityIds = array_merge(
                    $cityIds,
                    Db::name('earth_cities')->whereRaw('(cname LIKE ? OR name LIKE ?)', [$like, $like])->column('id')
                );
            }
    }

        $stateIds = array_values(array_unique(array_map('intval', $stateIds)));
        $cityIds = array_values(array_unique(array_map('intval', $cityIds)));

        // 3) 根据行政区ID在 machine 表中查 m_id
        $mids = [];
        if ($stateIds !== []) {
            $mids = array_merge($mids, Db::name('machine')->whereIn('state_id', $stateIds)->column('m_id'));
        }
        if ($cityIds !== []) {
            $mids = array_merge($mids, Db::name('machine')->whereIn('city_id', $cityIds)->column('m_id'));
        }

        return array_values(array_unique(array_map('intval', $mids)));
    }

    public static function wsPushPayload(string $event, array $payload, ?string $traceId = null): array
    {
        return [
            'event' => $event,
            'ts' => (int) floor(microtime(true) * 1000),
            'traceId' => $traceId ?: bin2hex(random_bytes(8)),
            'payload' => $payload,
        ];
    }
}
