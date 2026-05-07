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
        if ($regionType !== 'province' || $regionName === '') {
            return null;
        }
        $provMids = $this->queryProvinceMIds($regionName);
        if ($this->manager['pid'] > 0) {
            $auth = $this->app->machine->getAuthManagerMachineColumn(
                ['manager_id' => $this->manager['manager_id']],
                'm_id'
            );
            $auth = is_array($auth) ? $auth : (array) $auth;
            return array_values(array_unique(array_map('intval', array_intersect($auth, $provMids))));
        }
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
        } elseif ($this->manager['pid'] > 0) {
            $auth = $this->app->machine->getAuthManagerMachineColumn(
                ['manager_id' => $this->manager['manager_id']],
                'm_id'
            );
            if ($auth) {
                $q->whereIn('m_id', $auth);
            }
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
     * @param array{regionType?:string,regionName?:string,cycle?:string,machinePage?:int,machinePageSize?:int} $ctx
     * @return array<string,mixed>
     */
    public function buildSnapshot(array $ctx): array
    {
        $regionType = $ctx['regionType'] ?? 'national';
        $regionName = trim((string) ($ctx['regionName'] ?? ''));
        $cycle = $ctx['cycle'] ?? 'day';
        $page = max(1, (int) ($ctx['machinePage'] ?? 1));
        $pageSize = min(256, max(1, (int) ($ctx['machinePageSize'] ?? 128)));

        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        if (is_array($mScope) && $mScope === []) {
            return $this->emptySnapshot($regionType, $regionName, $cycle, $page, $pageSize);
        }

        $saleDataWhere = ['pay_status' => 3];
        if ($mScope !== null) {
            $saleDataWhere[] = ['m_id', 'in', $mScope];
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
        $chartResp = $this->app->saleOrders->getChartData($saleDataWhere, $chartType);
        $chartRows = is_array($chartResp) && isset($chartResp['data']) ? $chartResp['data'] : [];

        return [
            'serverTime' => date('Y-m-d H:i:s'),
            'regionType' => $regionType,
            'regionName' => $regionName,
            'deviceOverview' => $deviceOverview,
            'cargoStats' => $cargoStats,
            'tradeMetrics' => $tradeMetrics,
            'productSalesShare' => $this->buildProductSalesShare($saleDataWhere),
            'machineSalesShare' => $this->buildMachineSalesShare($saleDataWhere),
            'deviceSalesRank' => $this->buildDeviceSalesRank($saleDataWhere),
            'goodsPopularityRank' => $this->buildGoodsPopularityRank($saleDataWhere),
            'mapValues' => $this->buildMapValues($regionType, $regionName, $mScope),
            'salesTrend' => [
                'cycle' => $cycle,
                'points' => $this->chartRowsToPoints($cycle, $chartRows),
            ],
            'machineList' => $this->buildMachineList($mScope, $page, $pageSize, $screenCounts),
            'realtimeOrders' => $this->buildRealtimeOrders($saleDataWhere, $regionType, $regionName, 30),
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
        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        if (is_array($mScope) && $mScope === []) {
            return ['cycle' => $cycle, 'points' => []];
        }
        $saleDataWhere = ['pay_status' => 3];
        if ($mScope !== null) {
            $saleDataWhere[] = ['m_id', 'in', $mScope];
        }
        $chartType = $this->cycleToChartType($cycle);
        $chartResp = $this->app->saleOrders->getChartData($saleDataWhere, $chartType);
        $chartRows = is_array($chartResp) && isset($chartResp['data']) ? $chartResp['data'] : [];
        return [
            'cycle' => $cycle,
            'points' => $this->chartRowsToPoints($cycle, $chartRows),
        ];
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
                if (isset($row['countDate'])) {
                    $label = date('m-d', (int) $row['countDate']);
                }
            } elseif ($cycle === 'week') {
                $label = (string) ($row['week'] ?? '');
            } else {
                $label = (string) ($row['month'] ?? '');
            }
            $val = isset($row['totalPrice']) ? (float) $row['totalPrice'] : 0.0;
            if ($label !== '') {
                $points[] = ['label' => $label, 'value' => $val];
            }
        }
        return $points;
    }

    /**
     * @param array $saleWhere
     * @return array<int,array{label:string,value:int,percent:int}>
     */
    protected function buildProductSalesShare(array $saleWhere): array
    {
        $where = $this->saleWhereToQuery($saleWhere);
        $where[] = ['sod.g_id', '>', 0];
        $rows = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->where($where)
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
    protected function buildMachineSalesShare(array $saleWhere): array
    {
        $where = $this->saleWhereToQuery($saleWhere);
        $rows = Db::name('sale_orders')->alias('so')
            ->where($where)
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
    protected function buildDeviceSalesRank(array $saleWhere): array
    {
        $where = $this->saleWhereToQuery($saleWhere);
        $where[] = ['so.create_date', '>=', strtotime('-7 days')];
        $rows = Db::name('sale_orders')->alias('so')
            ->where($where)
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
    protected function buildGoodsPopularityRank(array $saleWhere): array
    {
        $where = $this->saleWhereToQuery($saleWhere);
        $where[] = ['sod.g_id', '>', 0];
        $since30 = strtotime('-30 days');
        $where[] = ['so.create_date', '>=', $since30];
        $rows = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->join('goods g', 'g.g_id = sod.g_id', 'left')
            ->join('goods_category gc', 'gc.gc_id = g.gc_id', 'left')
            ->where($where)
            ->field('sod.g_id,sod.g_name as name, IFNULL(SUM(sod.quantity - sod.refund_quantity),0) as value, IFNULL(gc.gc_name,"") as category')
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
        $rebuyMap = $this->fetchRebuyRatesForGids($gIds, $since30, $saleWhere);
        $out = [];
        foreach ($rows as $r) {
            $gid = (int) ($r['g_id'] ?? 0);
            $out[] = [
                'name' => (string) $r['name'],
                'value' => (int) round((float) $r['value']),
                'category' => (string) ($r['category'] ?? ''),
                'rebuyRate' => (int) ($rebuyMap[$gid] ?? 0),
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
     * @return array{level:string,provinceName:string,items:array<int,array{name:string,value:int,adcode:int}>}
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
     * @return array<int,array{name:string,value:int,adcode:int}>
     */
    protected function aggregateSalesByProvince(?array $mScope): array
    {
        $q = Db::name('sale_orders')->alias('so')
            ->join('machine m', 'm.m_id = so.m_id', 'left')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->where('so.pay_status', '=', 3)
            ->where('so.create_date', '>=', strtotime('-30 days'))
            ->field('IFNULL(s.cname,"") as name, IFNULL(s.code_full,s.code) as adcode, IFNULL(SUM(so.total_quantity),0) as v')
            ->group('m.state_id,s.cname,s.code,s.code_full')
            ->order('v', 'desc')
            ->limit(40);
        if ($mScope !== null) {
            $q->whereIn('so.m_id', $mScope);
        } elseif ($this->manager['pid'] > 0) {
            $auth = $this->app->machine->getAuthManagerMachineColumn(
                ['manager_id' => $this->manager['manager_id']],
                'm_id'
            );
            if ($auth) {
                $q->whereIn('so.m_id', $auth);
            }
        }
        $rows = $q->select()->toArray();
        $out = [];
        foreach ($rows as $r) {
            if ($r['name'] === '') {
                continue;
            }
            $adcode = (int) preg_replace('/\D/', '', (string) ($r['adcode'] ?? '0'));
            $out[] = [
                'name' => (string) $r['name'],
                'value' => (int) round((float) $r['v']),
                'adcode' => $adcode,
            ];
        }
        return $out;
    }

    /**
     * @param int[] $mids
     * @return array<int,array{name:string,value:int,adcode:int}>
     */
    protected function aggregateSalesByCity(array $mids): array
    {
        if ($mids === []) {
            return [];
        }
        $rows = Db::name('sale_orders')->alias('so')
            ->join('machine m', 'm.m_id = so.m_id', 'left')
            ->join('earth_cities c', 'm.city_id = c.id', 'left')
            ->where('so.pay_status', '=', 3)
            ->whereIn('so.m_id', $mids)
            ->where('so.create_date', '>=', strtotime('-30 days'))
            ->field('IFNULL(c.cname,"") as name, IFNULL(c.code_full,c.code) as adcode, IFNULL(SUM(so.total_quantity),0) as v')
            ->group('m.city_id,c.cname,c.code,c.code_full')
            ->order('v', 'desc')
            ->limit(40)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            if ($r['name'] === '') {
                continue;
            }
            $adcode = (int) preg_replace('/\D/', '', (string) ($r['adcode'] ?? '0'));
            $out[] = [
                'name' => (string) $r['name'],
                'value' => (int) round((float) $r['v']),
                'adcode' => $adcode,
            ];
        }
        return $out;
    }

    /**
     * @param int[]|null $mScope
     * @param array{total:int,operating:int,inStock:int,online:int,offline:int} $screenCounts
     * @return array<string,mixed>
     */
    protected function buildMachineList(?array $mScope, int $page, int $pageSize, array $screenCounts): array
    {
        $q = Db::name('machine')->alias('m')
            ->where('m.vending_machine_type', 1)
            ->field('m.m_id,m.machine_id,m.machine_name,m.online,m.street');
        if ($mScope !== null) {
            $q->whereIn('m.m_id', $mScope);
        } elseif ($this->manager['pid'] > 0) {
            $auth = $this->app->machine->getAuthManagerMachineColumn(
                ['manager_id' => $this->manager['manager_id']],
                'm_id'
            );
            if ($auth) {
                $q->whereIn('m.m_id', $auth);
            }
        }
        $total = (int) (clone $q)->count();
        $rows = $q->order('m.m_id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        $mIds = array_column($rows, 'm_id');
        $chStats = $this->channelStatsForMids($mIds);
        $salesMap = $this->todaySalesByMids($mIds);
        $list = [];
        foreach ($rows as $m) {
            $mid = (int) $m['m_id'];
            $st = $chStats[$mid] ?? ['emptyChannels' => 0, 'badChannels' => 0, 'emptySlots' => 0];
            $hours = '';
            $list[] = [
                'id' => (string) ($m['machine_id'] ?? ''),
                'name' => (string) ($m['machine_name'] ?? ''),
                'online' => (int) ($m['online'] ?? 0) === 1,
                'businessHours' => $hours,
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
            ->field('so.trade_no,so.machine_name,so.create_time,so.total_price,s.cname as state_name,(SELECT sod.g_name FROM sale_orders_details sod WHERE sod.order_id = so.order_id LIMIT 1) as g_name')
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
        $norm = preg_replace('/(省|市|壮族自治区|回族自治区|维吾尔自治区|自治区)$/', '', trim($regionName));
        $rows = Db::name('machine')->alias('m')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->where(function ($q) use ($norm, $regionName) {
                $q->whereLike('s.cname', '%' . $regionName . '%')
                    ->whereOrLike('s.cname', '%' . $norm . '%')
                    ->whereOrLike('s.name', '%' . $norm . '%');
            })
            ->column('m.m_id');
        return array_values(array_unique(array_map('intval', $rows)));
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
