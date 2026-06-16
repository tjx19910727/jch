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

    // /**
    //  * 归纳 getWhere / getList 权限规则，支持“全账号查询规则 + 指定账号测算结果”
    //  *
    //  * 说明：
    //  * - getWhere 指 `app/management/controller/Common.php::getWhere` 及其 `authNodeWhere`
    //  * - getList 指 `app/management/controller/machine/Machine.php::getList`
    //  *   到 `app/AppFactory/Management/Machine/MachineClient.php::getMList` 的权限链路
    //  *
    //  * @param array|null $managerContext 可传入任意账号上下文（manager_id/pid/ao_id/level）进行规则测算
    //  * @return array<string,mixed>
    //  */
    // public function summarizeGetWhereAndGetListAuthRules(?array $managerContext = null): array
    // {
    //     $ctx = is_array($managerContext) ? $managerContext : $this->manager;
    //     $pid = (int) ($ctx['pid'] ?? 0);
    //     $aoId = (int) ($ctx['ao_id'] ?? 0);
    //     $managerId = (int) ($ctx['manager_id'] ?? 0);
    //     $level = (int) ($ctx['level'] ?? 0);

    //     $permittedMachineIds = $this->resolvePermittedMachineIdsByManagerContext($ctx);
    //     $isSuperLike = ($aoId === 0 || $aoId === 1);

    //     return [
    //         'scope' => 'all_accounts_query_rule',
    //         'accountQueryRule' => [
    //             'table' => 'auth_manager',
    //             'alias' => 'am',
    //             'suggestedWhere' => [
    //                 ['am.is_del', '=', 2],
    //             ],
    //             'requiredFields' => ['manager_id', 'pid', 'ao_id', 'level'],
    //             'description' => '先按账号表批量拉取账号上下文（manager_id/pid/ao_id/level），再套用本方法规则计算每个账号权限范围。',
    //         ],
    //         'managerContext' => [
    //             'managerId' => $managerId,
    //             'pid' => $pid,
    //             'aoId' => $aoId,
    //             'level' => $level,
    //             'isSuperLikeAo' => $isSuperLike,
    //         ],
    //         'getWhereAuthRules' => [
    //             [
    //                 'rule' => '统一入口追加数据权限',
    //                 'source' => 'Common::getWhere -> authNodeWhere',
    //                 'detail' => '所有通过 getWhere 组装的 where，最终都会进入 authNodeWhere 进行数据权限补充。',
    //             ],
    //             [
    //                 'rule' => '按菜单 data_auth + d_type 生效',
    //                 'source' => 'Common::authNodeWhere',
    //                 'detail' => '仅当 currentMenu.data_auth=1 且 d_type>0 时，才追加数据权限过滤。',
    //             ],
    //             [
    //                 'rule' => 'd_type=2 按部门过滤',
    //                 'source' => 'Common::authNodeWhere',
    //                 'detail' => '非通用 API 时附加 ao_id=当前账号 ao_id。',
    //             ],
    //             [
    //                 'rule' => 'd_type>=3 按账号范围过滤',
    //                 'source' => 'Common::authNodeWhere',
    //                 'detail' => '通过节点配置字段（getAuthDataFieldByUrl）追加 [field in 可见账号ID]；d_type=5 仅直接下级。',
    //             ],
    //             [
    //                 'rule' => '组织树特殊规则',
    //                 'source' => 'Common::authNodeWhere',
    //                 'detail' => '顶级组织查询 /management/auth.auth_organization/getList 时改为 ao_id in 子组织集合。',
    //             ],
    //             [
    //                 'rule' => '超管组织豁免 ao_id',
    //                 'source' => 'Common::authNodeWhere',
    //                 'detail' => 'ao_id 为 0 或 1 时，移除 where 中 ao_id 限制。',
    //             ],
    //             [
    //                 'rule' => '销售订单列表特判',
    //                 'source' => 'Common::authNodeWhere',
    //                 'detail' => '当 ao_id>18 且 level=3 且 URL=/management/sale.sale_orders/getList，按子组织 ao_id in 过滤。',
    //             ],
    //             [
    //                 'rule' => 'meichitu 账号专项处理',
    //                 'source' => 'management/machine.machine/getList -> MachineClient::getMList',
    //                 'detail' => "当 \$this->manager['account'] == 'meichitu' 时，设备口径按 management/machine.machine/getList 处理：先走 getWhere 条件，再追加 vending_machine_type=1，并在 getMList 内按 resolvePermittedMachineIds 收敛（auth_manager_machine 绑定设备 + creator 自建设备；pid<=0 不限制）。",
    //             ],
    //         ],
    //         'getListAuthRules' => [
    //             [
    //                 'rule' => '设备列表固定只查主柜',
    //                 'source' => 'Machine::getList',
    //                 'detail' => '追加条件 vending_machine_type = 1。',
    //             ],
    //             [
    //                 'rule' => '设备分组前置过滤',
    //                 'source' => 'Machine::getList',
    //                 'detail' => '传 machine_group_id 时先换算 machine_id 集合，再追加 machine_id in (...)。',
    //             ],
    //             [
    //                 'rule' => 'getWhere 基础权限先执行',
    //                 'source' => 'Machine::getList',
    //                 'detail' => '列表 where 由 getWhere 生成，先带上 authNodeWhere 的数据权限规则。',
    //             ],
    //             [
    //                 'rule' => '账号设备范围二次收敛',
    //                 'source' => 'MachineClient::getMList',
    //                 'detail' => '非顶级账号(pid>0)仅可见 auth_manager_machine 绑定设备 + 自己创建设备（m_id in permitted）。',
    //             ],
    //         ],
    //         'permittedMachineQueryRule' => [
    //             'steps' => [
    //                 [
    //                     'step' => 1,
    //                     'when' => 'pid > 0',
    //                     'query' => 'SELECT m_id FROM auth_manager_machine WHERE manager_id = :manager_id',
    //                 ],
    //                 [
    //                     'step' => 2,
    //                     'when' => 'pid > 0',
    //                     'query' => 'SELECT m_id FROM machine WHERE creator = :manager_id',
    //                 ],
    //                 [
    //                     'step' => 3,
    //                     'when' => 'pid > 0',
    //                     'query' => '并集去重后得到 permitted m_id；pid <= 0 则返回 null（不限制）',
    //                 ],
    //             ],
    //         ],
    //         'effectiveResultForCurrentAccount' => [
    //             'machineScopeType' => $permittedMachineIds === null ? 'unrestricted' : 'restricted',
    //             'permittedMachineCount' => is_array($permittedMachineIds) ? count($permittedMachineIds) : null,
    //             'permittedMachineSample' => is_array($permittedMachineIds) ? array_slice(array_values($permittedMachineIds), 0, 20) : null,
    //         ],
    //     ];
    // }

    // /**
    //  * 按账号上下文模拟 MachineClient::resolvePermittedMachineIds 的规则
    //  *
    //  * @param array $ctx
    //  * @return int[]|null
    //  */
    // protected function resolvePermittedMachineIdsByManagerContext(array $ctx): ?array
    // {
    //     $account = (string) ($ctx['account'] ?? '');
    //     if ($account === 'meichitu') {
    //         $mIds = Db::name('machine_channel')->alias('mc')
    //             ->join('goods g', 'mc.g_id = g.g_id')
    //             ->where('g.gc_name', 'like', '%美驰图%')
    //             ->distinct(true)
    //             ->column('mc.m_id');
    //         return array_values(array_unique(array_map('intval', is_array($mIds) ? $mIds : [])));
    //     }

    //     $pid = (int) ($ctx['pid'] ?? 0);
    //     $managerId = (int) ($ctx['manager_id'] ?? 0);
    //     if ($pid <= 0 || $managerId <= 0) {
    //         return null;
    //     }

    //     $bindMids = Db::name('auth_manager_machine')->where('manager_id', $managerId)->column('m_id');
    //     $createMids = Db::name('machine')->where('creator', $managerId)->column('m_id');

    //     return array_values(array_unique(array_map('intval', array_merge(
    //         is_array($bindMids) ? $bindMids : [],
    //         is_array($createMids) ? $createMids : []
    //     ))));
    // }

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
     * 统一在营设备作用域（主柜 + 在营/外售组织口径）
     * @param int[]|null $mScope
     * @return int[]
     */
    protected function resolveOperatingMachineIds(?array $mScope, ?array $accountScope = null): array
    {
        if ($accountScope === null) {
            $accountScope = $this->resolveAccountMachineScope();
        }
        $finalScope = $this->intersectMachineScopes($mScope, $accountScope);

        $q = Db::name('machine')
            ->where('vending_machine_type', 1);
        $aoId = (int) ($this->manager['ao_id'] ?? 0);
        if (in_array($aoId, [1, 17], true)) {
            $q->where('is_operating', 1);
        } else {
            $q->whereIn('is_operating', [1, 3]);
        }
        if ($finalScope !== null) {
            if ($finalScope === []) {
                return [];
            }
            $q->whereIn('m_id', $finalScope);
        }
        $mids = $q->column('m_id');
        return array_values(array_unique(array_map('intval', is_array($mids) ? $mids : [])));
    }

    /**
     * 设备总览统计范围（主柜，不限定在营）
     * 口径：先按 resolvePermittedMachineIdsByManagerContext 取账号可见设备，再叠加区域 mScope
     *
     * @param int[]|null $mScope
     * @return int[]
     */
    protected function resolveDashboardMachineIds(?array $mScope, ?array $accountScope = null): array
    {
        if ($accountScope === null) {
            $accountScope = $this->resolveAccountMachineScope();
        }
        $finalScope = $this->intersectMachineScopes($mScope, $accountScope);

        $q = Db::name('machine')->where('vending_machine_type', 1);
        if ($finalScope !== null) {
            if ($finalScope === []) {
                return [];
            }
            $q->whereIn('m_id', $finalScope);
        }
        $mids = $q->column('m_id');
        return array_values(array_unique(array_map('intval', is_array($mids) ? $mids : [])));
    }

    /**
     * 按指定 m_id 列表统计设备总览
     * 口径：total 为 m_ids 数量；operating/inStock/online/offline 在该 m_ids 内统计
     *
     * @param int[] $mIds
     * @return array{total:int,operating:int,inStock:int,online:int,offline:int}
     */
    protected function buildMachineScreenCountsByMIds(array $mIds): array
    {
        $mIds = array_values(array_unique(array_map('intval', $mIds)));
        if ($mIds === []) {
            return ['total' => 0, 'operating' => 0, 'inStock' => 0, 'online' => 0, 'offline' => 0];
        }

        $row = Db::name('machine')
            ->whereIn('m_id', $mIds)
            ->fieldRaw(
                'SUM(CASE WHEN IFNULL(is_operating,0) = 1 THEN 1 ELSE 0 END) AS operating'
                . ', SUM(CASE WHEN IFNULL(is_operating,0) = 2 THEN 1 ELSE 0 END) AS in_stock'
                . ', SUM(CASE WHEN IFNULL(online,0) = 1 THEN 1 ELSE 0 END) AS online'
                . ', SUM(CASE WHEN IFNULL(online,0) = 2 THEN 1 ELSE 0 END) AS offline'
            )
            ->find();

        $a = is_array($row) ? $row : (is_object($row) ? $row->toArray() : []);
        return [
            'total' => count($mIds),
            'operating' => (int) ($a['operating'] ?? 0),
            'inStock' => (int) ($a['in_stock'] ?? 0),
            'online' => (int) ($a['online'] ?? 0),
            'offline' => (int) ($a['offline'] ?? 0),
        ];
    }

    /**
     * 账号维度设备范围（与 Machine::getList -> getMList 一致）
     * 返回 null 表示不限制；[] 表示无权限设备
     * @return int[]|null
     */
    protected function resolveAccountMachineScope(): ?array
    {
        return $this->app->machine->resolvePermittedMachineIds();
    }

    /**
     * 设备范围交集：null 表示不限制
     * @param int[]|null $a
     * @param int[]|null $b
     * @return int[]|null
     */
    protected function intersectMachineScopes(?array $a, ?array $b): ?array
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }
        return array_values(array_intersect(
            array_values(array_unique(array_map('intval', $a))),
            array_values(array_unique(array_map('intval', $b)))
        ));
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
        $cycle = trim((string) ($ctx['cycle'] ?? 'day'));
        if ($cycle === '') {
            $cycle = 'day';
        }
        $page = max(1, (int) ($ctx['machinePage'] ?? 1));
        $pageSize = min(256, max(1, (int) ($ctx['machinePageSize'] ?? 128)));
        $lastOrderId = max(0, (int) ($ctx['lastOrderId'] ?? 0));
        $timeRange = $this->parseCycleRange($cycle);

        $accountScope = $ctx['accountMachineScope'] ?? null;
        if ($accountScope === null) {
            // 第一步：先按 management/machine.machine/getList 口径取当前账号可见设备
            $accountScope = $this->resolveAccountMachineScope();
        }

        $authRuleSummary = [];
        // 第二步：再叠加 regionType / regionName 选择范围
        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        $dashboardScope = $this->resolveDashboardMachineIds($mScope, $accountScope);
        if ($dashboardScope === []) {
            return $this->emptySnapshot($regionType, $regionName, $cycle, $page, $pageSize, $authRuleSummary);
        }
        $operatingScope = $this->resolveOperatingMachineIds($mScope, $accountScope);
        $operatingScopeForQuery = $operatingScope === [] ? [0] : $operatingScope;

        // 第三步：按 cycle 追加时间条件，并统一用于后续业务查询
        $queryWhere = ['pay_status' => 3];
        $queryWhere[] = ['m_id', 'in', $operatingScopeForQuery];
        $this->appendTimeRangeToWhere($queryWhere, $timeRange['start'], $timeRange['end']);

        // 纯实时增量口径：不受 cycle 时间窗限制，仅保留设备范围与支付状态
        $orderIncrementWhere = ['pay_status' => 3];
        $orderIncrementWhere[] = ['m_id', 'in', $operatingScopeForQuery];

        $chartWhere = [['m_id', 'in', $operatingScopeForQuery]];
        // 当入参 cycle 为 day 时，salesTrend 希望展示最近 7 天的数据（包含今日），
        // 只调整用于图表查询的时间窗，不影响其它基于 $timeRange 的统计口径。
        $chartTimeRange = $timeRange;
        if ($this->normalizeCycleKeyword($cycle) === 'day') {
            // 与 parseCycleRange('week') 保持一致，使用最近 7 天作为起始点
            $chartTimeRange['start'] = strtotime('-7 days');
            $chartTimeRange['end'] = time();
        }
        $this->appendTimeRangeToWhere($chartWhere, $chartTimeRange['start'], $chartTimeRange['end']);

        $saleData = $this->app->saleOrders->getData($queryWhere);
        $screenCounts = $this->buildMachineScreenCountsByMIds($dashboardScope);
        $cargo = $this->app->machineChannel->getDataV2ByMIds($operatingScopeForQuery);

        // 纯实时增量口径：不受 cycle 时间窗限制，仅保留设备范围与支付状态
        $orderIncrementWhere = ['pay_status' => 3];
        $orderIncrementWhere[] = ['m_id', 'in', $operatingScopeForQuery];

        $chartWhere = [['m_id', 'in', $operatingScopeForQuery]];
        $this->appendTimeRangeToWhere($chartWhere, $timeRange['start'], $timeRange['end']);

        $saleData = $this->app->saleOrders->getData($queryWhere);
        $screenCounts = $this->buildMachineScreenCountsByMIds($dashboardScope);
        $cargo = $this->app->machineChannel->getDataV2ByMIds($operatingScopeForQuery);

        $todayOrders = (int) ($saleData['today']['saleQuantity'] ?? 0);
        $yesterdayOrders = (int) ($saleData['yesterday']['saleQuantity'] ?? 0);
        $todaySales = (float) ($saleData['today']['saleMoney'] ?? 0);
        $yesterdaySales = (float) ($saleData['yesterday']['saleMoney'] ?? 0);
        $avgOrder = $todayOrders > 0 ? round($todaySales / $todayOrders, 2) : 0.0;
        $tradeWhere = $this->saleWhereToQuery($queryWhere);
        $tradeStat = Db::name('sale_orders')->alias('so')
            ->where($tradeWhere)
            ->fieldRaw('COUNT(*) as cnt, IFNULL(SUM(so.total_price),0) as amount')
            ->find();
        $tradeStat = is_array($tradeStat) ? $tradeStat : (array) $tradeStat;
        $orderCountDelta = (int) ($tradeStat['cnt'] ?? 0);
        $salesAmountDelta = round((float) ($tradeStat['amount'] ?? 0), 2);

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
            'orderCountDelta' => $orderCountDelta,
            'salesAmountDelta' => $salesAmountDelta,
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
            'productSalesShare' => $this->buildProductSalesShare($queryWhere, $cycle),
            'machineSalesShare' => $this->buildMachineSalesShare($queryWhere, $cycle),
            'deviceSalesRank' => $this->buildDeviceSalesRank($queryWhere, $cycle),
            'goodsPopularityRank' => $this->buildGoodsPopularityRank($queryWhere, $cycle),
            'mapValues' => $this->buildMapValues($regionType, $regionName, $operatingScope),
            'salesTrend' => [
                'cycle' => $cycle,
                'points' => $this->chartRowsToPoints($cycle, $chartRows),
            ],
            // 'machineList' => $this->buildMachineList($mScope, $page, $pageSize, $screenCounts),
            'realtimeOrders' => $this->buildRealtimeOrders($queryWhere, $regionType, $regionName, 30),
            'orderIncrement' => $this->buildOrderIncrement($orderIncrementWhere, $lastOrderId, $regionType, $regionName),
        ];
    }

    /**
     * @return array{cycle:string,points:array<int,array{label:string,value:float|int}>}
     */
    public function buildSalesTrend(array $ctx): array
    {
        $regionType = $ctx['regionType'] ?? 'national';
        $regionName = trim((string) ($ctx['regionName'] ?? ''));
        $cycle = trim((string) ($ctx['cycle'] ?? 'day'));
        if ($cycle === '') {
            $cycle = 'day';
        }
        $debugEnabled = $this->shouldExposeAdcodeDebug();
        $timeRange = $this->parseCycleRange($cycle);
        $accountScope = $ctx['accountMachineScope'] ?? null;
        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        $operatingScope = $this->resolveOperatingMachineIds($mScope, $accountScope);
        if ($operatingScope === []) {
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
        $saleDataWhere[] = ['m_id', 'in', $operatingScope];
        $this->appendTimeRangeToWhere($saleDataWhere, $timeRange['start'], $timeRange['end']);
        $chartWhere = [['m_id', 'in', $operatingScope]];
        // 当 cycle 为 day 时，希望 salesTrend 返回最近 7 天的点，因此单独扩展图表查询时间窗
        $chartTimeRange = $timeRange;
        if ($this->normalizeCycleKeyword($cycle) === 'day') {
            $chartTimeRange['start'] = strtotime('-7 days');
            $chartTimeRange['end'] = time();
        }
        $this->appendTimeRangeToWhere($chartWhere, $chartTimeRange['start'], $chartTimeRange['end']);
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
     * @param array{regionType?:string,regionName?:string,page?:int,pageSize?:int,machinePage?:int,machinePageSize?:int,onlineStatus?:string} $ctx
     * @return array<string,mixed>
     */
    public function getMachineList(array $ctx): array
    {
        $regionType = $ctx['regionType'] ?? 'national';
        $regionName = trim((string) ($ctx['regionName'] ?? ''));
        $debugEnabled = $this->shouldExposeAdcodeDebug();
        $page = max(1, (int) ($ctx['page'] ?? ($ctx['machinePage'] ?? 1)));
        $pageSize = min(256, max(1, (int) ($ctx['pageSize'] ?? ($ctx['machinePageSize'] ?? 15))));
        $onlineStatus = strtolower(trim((string) ($ctx['onlineStatus'] ?? 'all')));
        if (!in_array($onlineStatus, ['all', 'online', 'offline'], true)) {
            $onlineStatus = 'all';
        }

        $accountScope = $ctx['accountMachineScope'] ?? null;
        $mScope = $this->effectiveMachineIds($regionType, $regionName);
        $operatingScope = $this->resolveOperatingMachineIds($mScope, $accountScope);
        if ($operatingScope === []) {
            return [
                'summary' => ['total' => 0, 'online' => 0, 'offline' => 0],
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => 0,
                'list' => [],
                'onlineStatus' => $onlineStatus,
            ];
        }

        $screenCounts = $this->buildMachineScreenCounts($operatingScope);
        $out = $this->buildMachineList($operatingScope, $page, $pageSize, $screenCounts, $onlineStatus);
        $out['onlineStatus'] = $onlineStatus;
        if ($debugEnabled) {
            $out['debugPaging'] = [
                'resolvedPage' => (int) ($out['page'] ?? $page),
                'resolvedPageSize' => (int) ($out['pageSize'] ?? $pageSize),
                'rawPage' => $ctx['page'] ?? ($ctx['machinePage'] ?? null),
                'rawPageSize' => $ctx['pageSize'] ?? ($ctx['machinePageSize'] ?? null),
            ];
            // $out['debugAccountScope'] = $this->buildAccountScopeDebug();
        }
        return $out;
    }

    protected function emptySnapshot(
        string $regionType,
        string $regionName,
        string $cycle,
        int $page,
        int $pageSize,
        array $authRuleSummary = []
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
                'orderCountDelta' => 0,
                'salesAmountDelta' => 0.0,

            ],
            'productSalesShare' => [],
            'machineSalesShare' => [],
            'deviceSalesRank' => [],
            'goodsPopularityRank' => [],
            'mapValues' => [
                'level' => in_array($regionType, ['province', 'city'], true) ? $regionType : 'national',
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
            'authPermissionRules' => $authRuleSummary,
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
        $key = $this->normalizeCycleKeyword($cycle);
        if ($key === 'week') {
            return 2;
        }
        if ($key === 'month') {
            return 3;
        }
        if ($key === 'day') {
            return 1;
        }
        $range = $this->parseCycleRange($cycle);
        if ($range['start'] !== null && $range['end'] !== null) {
            // 自定义区间按日粒度图表
            return 1;
        }
        return 1;
    }

    /**
     * salesTrend 在 day 口径下展示含今日的最近 7 个自然日。
     * @param array{start:?int,end:?int} $baseRange
     * @return array{start:?int,end:?int}
     */
    protected function salesTrendTimeRange(string $cycle, array $baseRange): array
    {
        if ($this->normalizeCycleKeyword($cycle) !== 'day') {
            return $baseRange;
        }

        $start = strtotime(date('Y-m-d', strtotime('-6 days')));
        return [
            'start' => $start === false ? ($baseRange['start'] ?? null) : $start,
            'end' => time(),
        ];
    }

    /**
     * 把 cycle 转为自义定查询起始时间戳（秒）
     * day => 当天 0 点
     * week => 最近 7 天
     * month => 最近 30 天
     */
    protected function cycleToSince(string $cycle, string $fallback = 'month'): ?int
    {
        $range = $this->parseCycleRange($cycle);
        if ($range['start'] !== null) {
            return $range['start'];
        }

        $c = $this->normalizeCycleKeyword($cycle);
        if ($c === null) {
            $c = $fallback;
        }
        $fallbackKey = $this->normalizeCycleKeyword($fallback);
        if (!in_array($c, ['day', 'week', 'month'], true)) {
            $c = $fallbackKey ?: 'month';
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
        $cycleKey = $this->normalizeCycleKeyword($cycle);
        if ($cycleKey === null) {
            $range = $this->parseCycleRange($cycle);
            $cycleKey = ($range['start'] !== null && $range['end'] !== null) ? 'day' : 'month';
        }
        $points = [];
        foreach ($chartRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = '';
            if ($cycleKey === 'day') {
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
            } elseif ($cycleKey === 'week') {
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
        if ($this->normalizeCycleKeyword($cycle) === 'day') {
            return $this->fillRecentWeekDailyPoints($points);
        }
        return $points;
    }

    /**
     * @param array<int,array{label:string,value:float|int}> $points
     * @return array<int,array{label:string,value:float|int}>
     */
    protected function fillRecentWeekDailyPoints(array $points): array
    {
        $values = [];
        foreach ($points as $point) {
            $label = (string) ($point['label'] ?? '');
            if ($label === '') {
                continue;
            }
            $values[$label] = ($values[$label] ?? 0) + (float) ($point['value'] ?? 0);
        }

        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $label = date('m-d', strtotime('-' . $i . ' days'));
            $out[] = [
                'label' => $label,
                'value' => $values[$label] ?? 0,
            ];
        }
        return $out;
    }

    /**
     * 兼容周期参数：day/week/month 与 日/周/月
     */
    protected function normalizeCycleKeyword(string $cycle): ?string
    {
        $c = strtolower(trim($cycle));
        if ($c === 'day' || $c === '日') {
            return 'day';
        }
        if ($c === 'week' || $c === '周') {
            return 'week';
        }
        if ($c === 'month' || $c === '月') {
            return 'month';
        }
        return null;
    }

    /**
     * 解析自定义区间：start~end（支持 ~ 或 ～）
     * @return array{start:?int,end:?int}
     */
    protected function parseCycleRange(string $cycle): array
    {
        $raw = trim($cycle);
        if ($raw === '') {
            return ['start' => null, 'end' => null];
        }

        $keyword = $this->normalizeCycleKeyword($raw);
        if ($keyword !== null) {
            if ($keyword === 'day') {
                return [
                    'start' => strtotime(date('Y-m-d')) ?: null,
                    'end' => time(),
                ];
            }
            if ($keyword === 'week') {
                return [
                    'start' => strtotime('-7 days') ?: null,
                    'end' => time(),
                ];
            }
            if ($keyword === 'month') {
                return [
                    'start' => strtotime('-30 days') ?: null,
                    'end' => time(),
                ];
            }
        }

        if (!preg_match('/^\s*(.+?)\s*[~～]\s*(.+?)\s*$/u', $raw, $m)) {
            return ['start' => null, 'end' => null];
        }
        $start = strtotime(trim($m[1]));
        $end = strtotime(trim($m[2]));
        if ($start === false || $end === false) {
            return ['start' => null, 'end' => null];
        }
        if ($start > $end) {
            $tmp = $start;
            $start = $end;
            $end = $tmp;
        }
        return ['start' => (int) $start, 'end' => (int) $end];
    }

    /**
     * 将 create_date 时间区间条件附加到 where
     * @param array<int,mixed> $where
     */
    protected function appendTimeRangeToWhere(array &$where, ?int $start, ?int $end): void
    {
        if ($start !== null) {
            $where[] = ['create_date', '>=', $start];
        }
        if ($end !== null) {
            $where[] = ['create_date', '<=', $end];
        }
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
     * @return array<int,array{name:string,value:int}>
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
            ->field('so.machine_name as name, IFNULL(SUM(so.total_quantity),0) as quantity, IFNULL(SUM(so.total_price),0) as value')
            ->group('so.m_id,so.machine_name')
            ->order('value', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'name' => (string) $r['name'],
                'value' => (int) round((float) ($r['value'] ?? 0)),
                'quantity' => round((float) ($r['quantity'] ?? 0), 2),
            ];
        }
        return $out;
    }

    /**
     * 设备营业额排行榜（按销售金额）
     * @param array $saleWhere
     * @param string $cycle
     * @return array<int,array{name:string,value:float}>
     */
    protected function buildDeviceRevenueRank(array $saleWhere, string $cycle = 'week'): array
    {
        $since = $this->cycleToSince($cycle, 'week');
        $where = $this->saleWhereToQuery($saleWhere);
        $rows = Db::name('sale_orders')->alias('so')
            ->where($where)
            ->when($since, function ($query) use ($since) {
                $query->where('so.create_date', '>=', $since);
            })
            ->field('so.machine_name as name, IFNULL(SUM(so.total_price),0) as value, IFNULL(SUM(so.total_quantity),0) as quantity')
            ->group('so.m_id,so.machine_name')
            ->order('value', 'desc')
            ->limit(10)
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['name' => (string) $r['name'], 'value' => round((float) ($r['value'] ?? 0), 2), 'quantity' => (int) round((float) ($r['quantity'] ?? 0))];
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
        if ($regionType === 'city' && $regionName !== '') {
            $mids = $mScope;
            if ($mids === null) {
                $mids = $this->queryProvinceMIds($regionName);
            }

            // 当前库内暂无独立区县维度聚合实现，先按城市作用域返回，避免错误回退到 national
            $items = $this->aggregateSalesByCity($mids);
            return [
                'level' => 'city',
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
    protected function buildMachineList(?array $mScope, int $page, int $pageSize, array $screenCounts, string $onlineStatus = 'all'): array
    {
        $page = max(1, (int) $page);
        $pageSize = min(256, max(1, (int) $pageSize));
        $offset = ($page - 1) * $pageSize;

        $q = Db::name('machine')->alias('m')
            ->join('machine_on_off moo', 'moo.m_id = m.m_id', 'left')
            ->join('earth_states s', 'm.state_id = s.id', 'left')
            ->join('earth_cities c', 'm.city_id = c.id', 'left')
            ->where('m.vending_machine_type', 1)
            ->where('m.is_operating', 1)
            ->field('m.m_id,m.machine_id,m.machine_name,m.online,m.http_online,m.street,m.state_id,m.city_id,m.regions_id,moo.on_off_machine,moo.on_off_ckc,IFNULL(s.cname,s.name) as state_name,IFNULL(c.cname,c.name) as city_name');
        if ($mScope !== null) {
            $q->whereIn('m.m_id', $mScope);
        }                                                                         
        if ($onlineStatus === 'online') {
            $q->where('m.online', 1);
        } elseif ($onlineStatus === 'offline') {
            $q->where('m.online', 2);
        }
        $total = (int) (clone $q)->count();
        $todayStart = strtotime(date('Y-m-d'));
        $rows = $q->order('m.online', 'asc')
            ->orderRaw('(SELECT IFNULL(SUM(so.total_quantity),0) FROM sale_orders so WHERE so.pay_status = 3 AND so.create_date >= ' . (int) $todayStart . ' AND so.m_id = m.m_id) DESC')
            ->limit($offset, $pageSize)
            ->select()
            ->toArray();
        $mIds = array_column($rows, 'm_id');
        $regionIds = array_values(array_unique(array_filter(array_map(static function ($row): int {
            return (int) ($row['regions_id'] ?? 0);
        }, $rows), static function (int $id): bool {
            return $id > 0;
        })));
        $regionNameMap = $this->resolveRegionNamesByIds($regionIds);
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
            $stateName = trim((string) ($m['state_name'] ?? ''));
            $cityName = trim((string) ($m['city_name'] ?? ''));
            $regionName = trim((string) ($regionNameMap[(int) ($m['regions_id'] ?? 0)] ?? ''));
            $street = trim((string) ($m['street'] ?? ''));
            $fullAddress = $this->joinAddressParts([$stateName, $cityName, $regionName, $street]);
            $list[] = [
                'id' => (string) ($m['machine_id'] ?? ''),
                'm_id' => $mid,
                'name' => (string) ($m['machine_name'] ?? ''),
                'online' => $m['online'],
                'businessHours' => (string) ($onOff['businessHours'] ?? ''),
                'on_off_machine' => $m['on_off_machine'] ?? '',
                'on_off_ckc' =>  $m['on_off_ckc'] ?? '',
                'address' => (string) ($m['street'] ?? ''),
                'full_address' => $fullAddress,
                'sales' => (int) ($salesMap[$mid] ?? 0),
                'salesQuantity' => (int) ($salesMap[$mid] ?? 0),
                'emptyChannels' => (int) $st['emptyChannels'],
                'badChannels' => (int) $st['badChannels'],
                'emptySlots' => (int) $st['emptySlots'],
            ];
        }
        $summaryTotal = (int) ($screenCounts['total'] ?? $total);
        $summaryOnline = (int) ($screenCounts['online'] ?? 0);
        $summaryOffline = (int) ($screenCounts['offline'] ?? 0);
        if ($onlineStatus === 'online') {
            $summaryTotal = $total;
            $summaryOnline = $total;
            $summaryOffline = 0;
        } elseif ($onlineStatus === 'offline') {
            $summaryTotal = $total;
            $summaryOnline = 0;
            $summaryOffline = $total;
        }
        return [
            'summary' => [
                'total' => $summaryTotal,
                'online' => $summaryOnline,
                'offline' => $summaryOffline,
            ],
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'list' => $list,
        ];
    }

    /**
     * @param int[] $regionIds
     * @return array<int,string>
     */
    protected function resolveRegionNamesByIds(array $regionIds): array
    {
        if ($regionIds === []) {
            return [];
        }

        $candidateTables = ['earth_regions', 'earth_areas', 'earth_area'];
        $map = [];
        foreach ($candidateTables as $table) {
            try {
                $rows = Db::name($table)
                    ->whereIn('id', $regionIds)
                    ->field('id,IFNULL(cname,name) as name')
                    ->select()
                    ->toArray();
                foreach ($rows as $row) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id <= 0 || isset($map[$id])) {
                        continue;
                    }
                    $map[$id] = trim((string) ($row['name'] ?? ''));
                }
            } catch (\Throwable $e) {
                continue;
            }
            if ($map !== []) {
                break;
            }
        }

        return $map;
    }

    /**
     * @param array<int,string> $parts
     */
    protected function joinAddressParts(array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $v = trim((string) $part);
            if ($v !== '') {
                $clean[] = $v;
            }
        }

        return implode('', $clean);
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
            ->field('m_id, IFNULL(SUM(total_quantity),0) as v')
            ->group('m_id')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['m_id']] = (int) round((float) $r['v']);
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
                'time' => isset($r['create_time']) ? date('Y-m-d H:i:s', (int) $r['create_time']) : '',
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
