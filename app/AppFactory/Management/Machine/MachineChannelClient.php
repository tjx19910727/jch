<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMainRelationTrait;
use app\AppFactory\Kernel\Traits\RemoteRemovalLog\RemoteRemovalLogTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineChannelClient extends ManagementClient
{
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait,MachineInfoTrait,MachineMainRelationTrait;
    use GoodsTrait,GoodsChangeTrait;
    use AuthManagerMachineTrait;
    use RemoteRemovalLogTrait;

    /**
     * 获取空槽、BAD、空货数量
     * @param $where
     * @return array
     */
    public function getData()
    {
        $empty = 0;
        $bad = 0;
        $stockOut = 0;
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $whereEmpty = $where;
            $whereEmpty['g_id'] = 0;
            $empty = $this->getMachineChannelCount($whereEmpty);

            $whereBad = $where;
            $whereBad['status'] = 3;
            $bad = $this->getMachineChannelCount($whereBad);

            $whereStockOut = $where;
            $whereStockOut['stock'] = 0;
            $whereStockOut[] = ['g_id', '>', 0];
            $stockOut = $this->getMachineChannelCount($whereStockOut);
        }
        $data = [
            "empty" => $empty,
            "bad" => $bad,
            "stockOut" => $stockOut,
        ];
        return $data;
    }

    /**
     * 按 m_id 列表统计空槽/BAD/空货（大屏等场景，与账号设备权限范围一致）
     *
     * @param int[] $mIds
     * @return array{empty:int,bad:int,stockOut:int}
     */
    public function getDataV2ByMIds(array $mIds): array
    {
        $mIds = array_values(array_unique(array_filter(array_map('intval', $mIds))));
        if ($mIds === []) {
            return ['empty' => 0, 'bad' => 0, 'stockOut' => 0];
        }

        $where = [
            ['m_id', 'in', $mIds],
            'raw' => 'EXISTS(SELECT 1 FROM machine m WHERE m.m_id = a.m_id AND m.is_operating = 1)',
        ];

        $whereEmpty = $where;
        $whereEmpty['g_id'] = 0;
        $empty = $this->getMachineChannelCountV2($whereEmpty);

        $whereBad = $where;
        $whereBad['status'] = 3;
        $bad = $this->getMachineChannelCountV2($whereBad);

        $whereStockOut = $where;
        $whereStockOut['stock'] = 0;
        $stockOut = $this->getMachineChannelCountV2($whereStockOut);

        return [
            'empty' => (int) $empty,
            'bad' => (int) $bad,
            'stockOut' => (int) $stockOut,
        ];
    }

        /**
     * 获取空槽、BAD、空货数量 V2
     * 如果machine_info表的sub_cabinet为2不取channel_position为2的数据
     * @return array
     */
    public function getDataV2()
    {
        $empty = 0;
        $bad = 0;
        $stockOut = 0;
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $where['raw'] = "EXISTS(SELECT 1 FROM machine m WHERE m.m_id = a.m_id AND m.is_operating = 1)";
            $whereEmpty = $where;
            $whereEmpty['g_id'] = 0;
            $empty = $this->getMachineChannelCountV2($whereEmpty);

            $whereBad = $where;
            $whereBad['status'] = 3;
            $bad = $this->getMachineChannelCountV2($whereBad);

            $whereStockOut = $where;
            $whereStockOut['stock'] = 0;
            $whereStockOut[] = ['g_id', '>', 0];
            $stockOut = $this->getMachineChannelCountV2($whereStockOut);
        }
        $data = [
            "empty" => $empty,
            "bad" => $bad,
            "stockOut" => $stockOut,
        ];
        return $data;
    }

    /**
     * 获取空槽货道列表
     * @param $where
     * @return array|string
     */
    public function getEmptyList($where)
    {
        $list = $this->buildEmptyListData($where);
        return $this->rQ($list);
    }

    /**
     * 获取Bad货道列表
     * @param $where
     * @return array|string
     */
    public function getBadList($where)
    {
        $list = $this->buildBadListData($where);
        return $this->rQ($list);
    }

    /**
     * 获取空货列表
     * @param $where
     * @return array|string
     */
    public function getStockOutList($where)
    {
        $list = $this->buildStockOutListData($where);
        return $this->rQ($list);
    }

    /**
     * 商品滞销设备列表
     * 筛选时间范围内，统计每台设备销量小于等于 sale_count 的货道数量
     * @param array $postData
     * @return array|string
     */
    public function getSlowMovingGoodsList($postData = [])
    {
        $pageNum = $postData['pageNum'] ?? 15;
        if (!is_numeric($pageNum) || $pageNum <= 0) {
            $pageNum = 15;
        }
        $pageNum = intval($pageNum);

        $page = $postData['page'] ?? 1;
        if (!is_numeric($page) || $page <= 0) {
            $page = 1;
        }
        $page = intval($page);

        $context = $this->buildSlowMovingQueryContext($postData);
        if ($context['empty']) {
            return $this->rQ([
                'total' => 0,
                'per_page' => $pageNum,
                'current_page' => $page,
                'last_page' => 1,
                'data' => [],
            ]);
        }

        $saleByMcSubQuery = $context['sale_sub_mc_query'];
        $saleCount = $context['sale_count'];
        $queryMIds = $context['query_m_ids'];
        $soWhere = $context['so_where'];

        $saleNumExpr = 'IFNULL(sm.sale_num,0)';
        $slowCountExpr = 'sum(case when mc.g_id > 0 and (' . $saleNumExpr . ') <= ' . $saleCount . ' then 1 else 0 end)';

        $query = Db::name('machine_channel')
            ->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id', 'left')
            ->leftJoin([$saleByMcSubQuery => 'sm'], 'sm.sale_m_id = mc.m_id and sm.mc_id = mc.mc_id')
            ->where('m.is_operating', 1)
            ->where('mc.status', '<>', 2)
            ->whereRaw('(mc.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = mc.m_id AND mi.sub_cabinet = 1))')
            ->field('mc.m_id,m.machine_id,m.machine_name,' . $slowCountExpr . ' channel_count,sum(case when mc.g_id = 0 OR mc.stock = 0 then 1 else 0 end) empty_channel')
            ->group('mc.m_id,m.machine_id,m.machine_name')
            ->having($slowCountExpr . ' > 0')
            ->order('channel_count desc,m.machine_id asc');

        if ($queryMIds !== null) {
            $query->whereIn('mc.m_id', $queryMIds);
        }

        $debugQuery = clone $query;
        $mainSql = $debugQuery->fetchSql(true)->select();
        actionLog('[slow_moving] sale_sub_mc_sql: ' . $saleByMcSubQuery);
        actionLog('[slow_moving] main_sql: ' . $mainSql);
        actionLog('[slow_moving] so_where: ' . json_encode($soWhere, JSON_UNESCAPED_UNICODE));
        actionLog('[slow_moving] filter_m_ids: ' . json_encode($queryMIds, JSON_UNESCAPED_UNICODE));

        $pageData = $query->paginate($pageNum, false, ['page' => $page, 'query' => request()->param()])->toArray();
        return $this->rQ($pageData);
    }

    /**
     * 导出商品滞销设备列表
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function exportSlowMovingGoodsList($postData = [])
    {
        $context = $this->buildSlowMovingQueryContext($postData);
        if ($context['empty']) {
            return $this->rNoData();
        }

        $saleByMcSubQuery = $context['sale_sub_mc_query'];
        $saleCount = $context['sale_count'];
        $queryMIds = $context['query_m_ids'];

        $saleNumExpr = 'IFNULL(sm.sale_num,0)';
        $slowCountExpr = 'sum(case when mc.g_id > 0 and (' . $saleNumExpr . ') <= ' . $saleCount . ' then 1 else 0 end)';

        $query = Db::name('machine_channel')
            ->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id', 'left')
            ->leftJoin([$saleByMcSubQuery => 'sm'], 'sm.sale_m_id = mc.m_id and sm.mc_id = mc.mc_id')
            ->where('mc.status', '<>', 2)
            ->where('m.is_operating', 1)
            ->whereRaw('(mc.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = mc.m_id AND mi.sub_cabinet = 1))')
            ->field('mc.m_id,m.machine_id,m.machine_name,' . $slowCountExpr . ' channel_count,sum(case when mc.g_id = 0 OR mc.stock = 0 then 1 else 0 end) empty_channel')
            ->group('mc.m_id,m.machine_id,m.machine_name')
            ->having($slowCountExpr . ' > 0')
            ->order('channel_count desc,m.machine_id asc');

        if ($queryMIds !== null) {
            $query->whereIn('mc.m_id', $queryMIds);
        }

        $list = $query->select();
        if (!$list) {
            return $this->rNoData();
        }

        $list = $list->toArray();
        $title = [
            'machine_id' => '设备编号',
            'machine_name' => '设备名称',
            'channel_count' => '滞销货道数',
            'empty_channel' => '空货道数',
        ];
        $filename = '商品滞销设备列表-' . date('YmdHis');
        return $this->sendToExport('首页-商品滞销设备列表', $filename, $title, $list);
    }

    /**
     * 商品滞销设备货道详情
     * 传入 m_id，返回满足筛选条件的滞销货道和空货货道
     * @param array $postData
     * @return array|string
     */
    public function getSlowMovingGoodsDetail($postData = [])
    {
        if (empty($postData['m_id'])) {
            return $this->r(100, 'm_id不能为空');
        }

        $context = $this->buildSlowMovingQueryContext($postData);
        if ($context['empty']) {
            return $this->rQ([]);
        }

        $detailMId = $postData['m_id'];
        if (is_array($detailMId)) {
            $detailMId = reset($detailMId);
        }
        $detailMId = trim((string)$detailMId);
        if (strpos($detailMId, ',') !== false) {
            $detailMId = trim(explode(',', $detailMId)[0]);
        }
        if ($detailMId === '' || $detailMId === '0') {
            return $this->r(100, 'm_id不能为空');
        }

        if ($context['query_m_ids'] !== null && !in_array($detailMId, array_map('strval', $context['query_m_ids']), true)) {
            return $this->rQ([]);
        }

        $saleByMcSubQuery = $context['sale_sub_mc_query'];

        $saleCount = $context['sale_count'];

        $saleNumExpr = 'IFNULL(sm.sale_num,0)';
        $isSlowExpr = '(mc.g_id > 0 and (' . $saleNumExpr . ') <= ' . $saleCount . ')';
        $isEmptyExpr = '(mc.g_id = 0 OR mc.stock = 0)';

        $query = Db::name('machine_channel')
            ->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id', 'left')
            ->leftJoin([$saleByMcSubQuery => 'sm'], 'sm.sale_m_id = mc.m_id and sm.mc_id = mc.mc_id')
            ->where('mc.status', '<>', 2)
            ->where('mc.m_id', '=', $detailMId)
            ->whereRaw('(mc.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = mc.m_id AND mi.sub_cabinet = 1))')
            ->whereRaw('(' . $isSlowExpr . ' OR ' . $isEmptyExpr . ')')
            ->field('mc.m_id,m.machine_id,m.machine_name,mc.mc_id,mc.channel_code,mc.g_id,mc.g_name,mc.stock,' . $saleNumExpr . ' sale_num,case when ' . $isSlowExpr . ' then 1 else 0 end is_slow,case when ' . $isEmptyExpr . ' then 1 else 0 end is_empty')
            ->order('mc.channel_code asc');

        $list = $query->select();
        $rows = $list ? $list->toArray() : [];

        $machineInfo = [
            'm_id' => $detailMId,
            'machine_id' => '',
            'machine_name' => '',
        ];
        if ($rows) {
            $machineInfo['m_id'] = $rows[0]['m_id'] ?? $detailMId;
            $machineInfo['machine_id'] = $rows[0]['machine_id'] ?? '';
            $machineInfo['machine_name'] = $rows[0]['machine_name'] ?? '';
        }

        $slowChannelList = [];
        $emptyChannelList = [];
        foreach ($rows as $item) {
            $channelItem = [
                'mc_id' => $item['mc_id'] ?? 0,
                'channel_code' => $item['channel_code'] ?? '',
                'g_id' => $item['g_id'] ?? 0,
                'g_name' => $item['g_name'] ?? '',
                'stock' => $item['stock'] ?? 0,
                'sale_num' => $item['sale_num'] ?? 0,
            ];

            if (!empty($item['is_slow'])) {
                $slowChannelList[] = $channelItem;
            }
            if (!empty($item['is_empty'])) {
                $emptyChannelList[] = $channelItem;
            }
        }

        return $this->rQ([
            'm_id' => $machineInfo['m_id'],
            'machine_id' => $machineInfo['machine_id'],
            'machine_name' => $machineInfo['machine_name'],
            'slow_channel_list' => $slowChannelList,
            'empty_channel_list' => $emptyChannelList,
        ]);
    }

    /**
     * 商品滞销汇总（最近15天销量为0）
     * 滞销货道数：沿用滞销货道口径（mc_id维度，销量<=0）
     * 滞销商品数：在营设备货道上的商品ID中，最近15天销量为0的商品数
     * @param array $postData
     * @return array|string
     */
    public function getSlowMovingGoodsSummary($postData = [])
    {
        $postData['sale_count'] = 0;
        $postData['countDate'] = '';

        $context = $this->buildSlowMovingQueryContext($postData);
        if ($context['empty']) {
            return $this->rQ([
                'slow_goods_count' => 0,
                'slow_channel_count' => 0,
            ]);
        }

        $queryMIds = $context['query_m_ids'];
        $saleWhere = $context['sale_where'];
        $saleByMcSubQuery = $context['sale_sub_mc_query'];

        $slowChannelCount = Db::name('machine_channel')
            ->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id', 'left')
            ->leftJoin([$saleByMcSubQuery => 'sm'], 'sm.sale_m_id = mc.m_id and sm.mc_id = mc.mc_id')
            ->where('m.is_operating', 1)
            ->where('mc.status', '<>', 2)
            ->where('mc.g_id', '>', 0)
            ->whereRaw('(mc.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = mc.m_id AND mi.sub_cabinet = 1))')
            ->whereRaw('IFNULL(sm.sale_num,0) <= 0');
        if ($queryMIds !== null) {
            $slowChannelCount->whereIn('mc.m_id', $queryMIds);
        }
        $slowChannelCount = $slowChannelCount->count();

        $saleGoodsExpr = 'IFNULL(NULLIF(sod.g_id,0),IFNULL(sod.g_id,0))';
        $saleByGoodsSubQuery = Db::name('sale_orders_details')
            ->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id', 'left')
            ->where($saleWhere)
            ->field($saleGoodsExpr . ' sale_g_id,sum(sod.quantity) sale_num')
            ->whereRaw($saleGoodsExpr . ' > 0')
            ->group($saleGoodsExpr)
            ->buildSql();

        $slowGoodsCount = Db::name('machine_channel')
            ->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id', 'left')
            ->leftJoin([$saleByGoodsSubQuery => 'sg'], 'sg.sale_g_id = mc.g_id')
            ->where('m.is_operating', 1)
            ->where('mc.status', '<>', 2)
            ->where('mc.g_id', '>', 0)
            ->whereRaw('(mc.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = mc.m_id AND mi.sub_cabinet = 1))')
            ->whereRaw('IFNULL(sg.sale_num,0) <= 0');
        if ($queryMIds !== null) {
            $slowGoodsCount->whereIn('mc.m_id', $queryMIds);
        }
        $slowGoodsCount = $slowGoodsCount->count('distinct mc.g_id');

        return $this->rQ([
            'slow_goods_count' => $slowGoodsCount,
            'slow_channel_count' => $slowChannelCount,
        ]);
    }

    /**
     * 构建滞销查询上下文
     * @param array $postData
     * @return array
     */
    private function buildSlowMovingQueryContext($postData = [])
    {
        $saleCount = $postData['sale_count'] ?? 0;
        if ($saleCount === '' || !is_numeric($saleCount)) {
            $saleCount = 0;
        }

        $soWhere = $postData['so_where'] ?? [];
        list($startDate, $endDate) = $this->resolveSlowMovingDateRange($postData['countDate'] ?? '');
        $filterMIds = $this->resolveSlowMovingMIds($postData);

        $queryMIds = null;
        if ($filterMIds !== null) {
            if (!$filterMIds) {
                return [
                    'empty' => true,
                    'sale_count' => $saleCount,
                    'so_where' => $soWhere,
                    'query_m_ids' => [],
                    'sale_sub_query' => '',
                ];
            }
            $queryMIds = $filterMIds;
        }

        $saleWhere = [
            ['so.pay_status', '=', 3],
            ['so.create_date', 'between', [$startDate, $endDate]],
        ];
        if ($soWhere) {
            $saleWhere = array_merge($saleWhere, $soWhere);
        }
        if ($queryMIds !== null) {
            $saleWhere[] = ['so.m_id', 'in', $queryMIds];
        }

        $saleByMcSubQuery = Db::name('sale_orders_details')
            ->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id', 'left')
            ->where($saleWhere)
            ->where('sod.mc_id', '>', 0)
            ->field('so.m_id sale_m_id,sod.mc_id,sum(sod.quantity) sale_num')
            ->group('so.m_id,sod.mc_id')
            ->buildSql();

        return [
            'empty' => false,
            'sale_count' => $saleCount,
            'so_where' => $soWhere,
            'sale_where' => $saleWhere,
            'query_m_ids' => $queryMIds,
            'sale_sub_mc_query' => $saleByMcSubQuery,
        ];
    }

    /**
     * 组装滞销列表分页结构
     * @param array $list
     * @param int $pageNum
     * @param int $page
     * @return array
     */
    // private function buildSlowMovingPageData($list, $pageNum, $page)
    // {
    //     $total = count($list);
    //     $lastPage = $total > 0 ? intval(ceil($total / $pageNum)) : 1;
    //     if ($page > $lastPage) {
    //         $page = $lastPage;
    //     }
    //     if ($page < 1) {
    //         $page = 1;
    //     }

    //     $offset = ($page - 1) * $pageNum;
    //     $pageList = array_slice($list, $offset, $pageNum);

    //     return [
    //         'total' => $total,
    //         'per_page' => $pageNum,
    //         'current_page' => $page,
    //         'last_page' => $lastPage,
    //         'data' => array_values($pageList),
    //     ];
    // }

    /**
     * 解析滞销筛选时间，默认最近15天（含今天）
     * @param string $dateRange
     * @return array
     */
    private function resolveSlowMovingDateRange($dateRange = '')
    {
        $startDate = strtotime(date('Y-m-d', strtotime('-14 days')));
        $endDate = strtotime(date('Y-m-d 23:59:59'));

        if (!$dateRange) {
            return [$startDate, $endDate];
        }

        $parts = explode('~', $dateRange);
        if (!isset($parts[0]) || !isset($parts[1])) {
            return [$startDate, $endDate];
        }

        $startTime = strtotime(trim($parts[0]));
        $endTime = strtotime(trim($parts[1]));
        if ($startTime === false || $endTime === false) {
            return [$startDate, $endDate];
        }
        return [$startTime, $endTime];
    }

    /**
     * 解析设备筛选（m_id / machine_group_id）
     * @param array $postData
     * @return array|null null表示无设备筛选
     */
    private function resolveSlowMovingMIds($postData)
    {
        $machineWhere = [];
        $hasMachineFilter = false;

        if (isset($postData['m_id']) && $postData['m_id'] !== '') {
            $mIdValue = $postData['m_id'];
            if (!is_array($mIdValue)) {
                $mIdValue = explode(',', (string)$mIdValue);
            }
            $mIdValue = array_values(array_filter(array_map('trim', $mIdValue), function ($value) {
                return $value !== '' && $value !== '0';
            }));
            if ($mIdValue) {
                $machineWhere[] = ['m_id', 'in', $mIdValue];
                $hasMachineFilter = true;
            }
        }

        if (isset($postData['machine_group_id']) && $postData['machine_group_id'] !== '' && $postData['machine_group_id'] != 0) {
            $groupMIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'm_id');
            if (!$groupMIds) {
                return [];
            }
            $machineWhere[] = ['m_id', 'in', $groupMIds];
            $hasMachineFilter = true;
        }

        if (!$hasMachineFilter) {
            return null;
        }

        $mIds = $this->getMachineColumn($machineWhere, 'm_id');
        if (!$mIds) {
            return [];
        }
        return array_values(array_unique($mIds));
    }

    /**
     * 导出空槽列表
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportEmptyList($where = [])
    {
        $list = $this->buildEmptyListData($where);
        if (!$list) return $this->rNoData();

        $title = [
            "machine_id" => "设备编号",
            "machine_name" => "设备名称",
            "total_channel" => "总货道数",
            "empty_num" => "空槽数",
            "empty_channel" => "空槽编号",
            "empty_ratio" => "空槽占比",
        ];
        $filename = "首页-空槽列表-" . date("YmdHis");
        return $this->sendToExport("首页-空槽列表", $filename, $title, $list);
    }

    /**
     * 导出Bad列表
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportBadList($where = [])
    {
        $list = $this->buildBadListData($where);
        if (!$list) return $this->rNoData();

        $title = [
            "machine_id" => "设备编号",
            "machine_name" => "设备名称",
            "total_channel" => "总货道数",
            "bad_num" => "BAD数",
            "bad_channel" => "BAD槽位",
            "bad_ratio" => "BAD占比",
        ];
        $filename = "首页-BAD列表-" . date("YmdHis");
        return $this->sendToExport("首页-BAD列表", $filename, $title, $list);
    }

    /**
     * 导出空货列表
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportStockOutList($where = [])
    {
        $list = $this->buildStockOutListData($where);
        if (!$list) return $this->rNoData();

        $title = [
            "machine_id" => "设备编号",
            "machine_name" => "设备名称",
            "total_channel" => "总货道数",
            "stock_out_num" => "空货数",
            "stock_out_channel" => "基础机组空货槽位",
            "stock_out_channel_arc" => "弧柜空货槽位",
            "stock_out_ratio" => "空货占比",
        ];
        $filename = "首页-空货列表-" . date("YmdHis");
        return $this->sendToExport("首页-空货列表", $filename, $title, $list);
    }

    /**
     * 导出单设备货道库存明细
     * @param mixed $deviceId
     * @return array|string
     */
    public function exportStockRatioByMachine($deviceId)
    {
        $machine = $this->getMachineFind(['m_id'=>$deviceId], 'm_id,machine_id,machine_name');
        if (!$machine) {
            return $this->r(100, $this->lang('VMachine.machine_no_data'));
        }

        $field = 'channel_code,g_name,retail_price,capacity,stock';
        $list = $this->getMachineChannelList(['m_id' => $machine['m_id']], 0, $field, 'channel_code asc');
        if (!$list) {
            return $this->rNoData();
        }

        $list = $list->toArray();
        foreach ($list as $key => $item) {
            $item['channel_name'] = '货道' . $item['channel_code'];
            $list[$key] = $item;
        }

        $title = [
            'channel_code' => '货道编号',
            'channel_name' => '货道名称',
            'g_name' => '商品名称',
            'retail_price' => '商品售价',
            'capacity' => '货道容量',
            'stock' => '货道库存',
        ];
        $filename = '设备货道库存明细-' . $machine['machine_id'] . '-' . date('YmdHis');
        return $this->sendToExport('设备管理-设备货道库存明细', $filename, $title, $list);
    }

    /**
     * 构建空槽列表数据
     * @param array $where
     * @return array
     */
    private function buildEmptyListData($where = [])
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        $where[] = ['status', '<>', 2];
        $where['g_id'] = 0;
        $expr = "(a.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
        $exprOperating = "EXISTS(SELECT 1 FROM machine m WHERE m.m_id = a.m_id AND m.is_operating = 1)";
        if (!empty($where['raw'])) {
            $where['raw'] .= " AND " . $expr . " AND " . $exprOperating;
        } else {
            $where['raw'] = $expr . " AND " . $exprOperating;
        }
        $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) empty_num', '', '', 'm_id');
        if (!$list) return [];

        $list = $list->toArray();
        foreach ($list as $key => $value) {
            $whereEmpty = [];
            $sub_cabinet = $this->getMachineInfoValue(['m_id' => $value['m_id']], 'sub_cabinet');
            if (!$sub_cabinet || $sub_cabinet == 2) $whereEmpty['channel_position'] = 1;
            $whereEmpty['m_id'] = $value['m_id'];
            $whereEmpty[] = ['status', "<>", 2];
            $value['total_channel'] = $this->getMachineChannelCount($whereEmpty);

            $whereEmpty['g_id'] = 0;
            $emptyList = $this->getMachineChannelColumn($whereEmpty, 'channel_code');
            $value['empty_channel'] = implode(",", $emptyList ?? []);
            $value['empty_ratio'] = $value['total_channel'] > 0 ? (bcmul(bcdiv($value['empty_num'], $value['total_channel'], 3), 100, 1) . "%") : "0%";
            $list[$key] = $value;
        }
        return $list;
    }

    /**
     * 构建Bad列表数据
     * @param array $where
     * @return array
     */
    private function buildBadListData($where = [])
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $where[] = ['m_id', 'in', $mIds];
        }
        $where['status'] = 3;
        $expr = "(a.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
        $exprOperating = "EXISTS(SELECT 1 FROM machine m WHERE m.m_id = a.m_id AND m.is_operating = 1)";
        if (!empty($where['raw'])) {
            $where['raw'] .= " AND " . $expr . " AND " . $exprOperating;
        } else {
            $where['raw'] = $expr . " AND " . $exprOperating;
        }
        $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id,
            (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name,
            count(mc_id) bad_num', '', '', 'm_id');
        if (!$list) return [];

        $list = $list->toArray();
        foreach ($list as $key => $value) {
            $whereBad = [];
            $sub_cabinet = $this->getMachineInfoValue(['m_id' => $value['m_id']], 'sub_cabinet');
            if (!$sub_cabinet || $sub_cabinet == 2) $whereBad['channel_position'] = 1;
            $whereBad['m_id'] = $value['m_id'];
            $value['total_channel'] = $this->getMachineChannelCount($whereBad);
            $whereBad['status'] = 3;
            $badList = $this->getMachineChannelColumn($whereBad, 'channel_code');
            $value['bad_channel'] = implode(",", $badList ?? []);
            $value['bad_ratio'] = $value['total_channel'] > 0 ? (bcmul(bcdiv($value['bad_num'], $value['total_channel'], 3), 100, 1) . "%") : "0%";
            $list[$key] = $value;
        }
        return $list;
    }

    /**
     * 构建空货列表数据
     * @param array $where
     * @return array
     */
    private function buildStockOutListData($where = [])
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $where[] = ['m_id', 'in', $mIds];
        }
        $where['stock'] = 0;
        $where[] = ['g_id', '>', 0];
        $expr = "(a.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
        $exprOperating = "EXISTS(SELECT 1 FROM machine m WHERE m.m_id = a.m_id AND m.is_operating = 1)";
        if (!empty($where['raw'])) {
            $where['raw'] .= " AND " . $expr . " AND " . $exprOperating;
        } else {
            $where['raw'] = $expr . " AND " . $exprOperating;
        }
        $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
            (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
            count(mc_id) stock_out_num', '', '', 'm_id');
        if (!$list) return [];

        $list = $list->toArray();
        foreach ($list as $key => $value) {
            $whereTotal = [];
            $sub_cabinet = $this->getMachineInfoValue(['m_id' => $value['m_id']], 'sub_cabinet');
            if (!$sub_cabinet || $sub_cabinet == 2) $whereTotal['channel_position'] = 1;

            $whereTotal['m_id'] = $value['m_id'];
            $value['total_channel'] = $this->getMachineChannelCount($whereTotal);

            $whereStockOutBase = ['m_id' => $value['m_id'], 'channel_position' => 1, 'stock' => 0];
            $whereStockOutBase[] = ['g_id', '>', 0];
            $stockOutList = $this->getMachineChannelColumn($whereStockOutBase, 'channel_code');
            $value['stock_out_channel'] = implode(",", $stockOutList ?? []);

            $whereStockOutArc = ['m_id' => $value['m_id'], 'channel_position' => 2, 'stock' => 0];
            $whereStockOutArc[] = ['g_id', '>', 0];
            $stockOutArcList = $this->getMachineChannelColumnV2($whereStockOutArc, 'channel_code');
            $value['stock_out_channel_arc'] = implode(",", $stockOutArcList ?? []);

            $value['stock_out_ratio'] = $value['total_channel'] > 0 ? (bcmul(bcdiv($value['stock_out_num'], $value['total_channel'], 3), 100, 1) . "%") : "0%";
            $list[$key] = $value;
        }
        return $list;
    }

    /**
     * 修改货道信息
     * @param $postData
     * @return array|string
     */
    public function updateMc($postData)
    {
        $mc = $this->getMachineChannelFind(['mc_id' => $postData['mc_id']],'m_id,channel_position,machine_id,mc_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,out_fail_stock,status');
        if (!$mc) return $this->r(100, $this->lang("VMachineChannel.mc_no_data"));
        $mc = obj2arr($mc);
        //如果是货道是边柜，查询主柜信息
        // if(isset($mc['channel_position']) && $mc['channel_position'] == 3){
        //     $main_m_id = $this->getMachineMainRelationValue(['b_mc_id' => $mc['m_id']], 'main_mc_id');
        //     $mc['m_id'] = $main_m_id;
        // }
        $machine = $this->getMachineFind(['m_id' => $mc['m_id']],'m_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->r(100, $this->lang("VMachine.machine_no_data"));
        // 商品变化基础数据
        $insertGChange = [
            "m_id" => $machine['m_id'],
            "machine_id" => $machine['machine_id'],
            "machine_name" => $machine['machine_name'],
            "mc_id" => $mc['mc_id'],
            "channel_code" => $mc['channel_code'],
            "mg_id" => $mc['mg_id'],
            "g_id" => $mc['g_id'],
            "g_name" => $mc['g_name'],
            "gc_id" => $mc['gc_id'],
            "gc_name" => $mc['gc_name'],
            "pic" => $mc['pic'],
            "sku" => $mc['sku'],
            "bar_code" => $mc['bar_code'],
            "ao_id" => $machine['ao_id'],
        ];

        $this->startTrans();
        try {
            // ========== 多商品批次处理 ==========
            $isMultiGoods = isset($postData['is_multi_goods']) && intval($postData['is_multi_goods']) === 1;
            $batchArr = isset($postData['batch_arr']) ? $postData['batch_arr'] : [];

            if ($isMultiGoods) {
                // 校验：batch_arr 至少 1 个（加上队首 ≥2）
                if (empty($batchArr) || count($batchArr) < 1) {
                    $this->rollbackTrans();
                    return $this->r(100, '开启多商品模式必须设置多个商品');
                }
                // 校验：队首商品必须有 g_id
                if (!isset($postData['g_id']) || intval($postData['g_id'] ?? 0) <= 0) {
                    $this->rollbackTrans();
                    return $this->r(100, '开启多商品模式必须设置商品');
                }
                // 总库存不超容量
                $totalStock = intval($postData['stock'] ?? 0);
                $checkGoodsIds = [];
                foreach ($batchArr as $item) {
                    $totalStock += intval($item['stock'] ?? 0);
                    if (isset($item['g_id']) && intval($item['g_id'] ?? 0) > 0) {
                        $checkGoodsIds[] = $item['g_id'];
                    }
                }
                if(count($checkGoodsIds) != count($batchArr)) {
                    $this->rollbackTrans();
                    return $this->r(100, '开启多商品模式必须设置有效的商品');
                }
                $capacity = intval($mc['capacity'] ?? 0);
                if ($totalStock > $capacity) {
                    $this->rollbackTrans();
                    return $this->r(100, '批次商品总库存(' . $totalStock . ')超过货道容量(' . $capacity . ')');
                }

                // 构建队首数据（来自 $postData）
                $headData = [
                    'g_id'             => $postData['g_id'] ?? 0,
                    'stock'            => $postData['stock'] ?? 0,
                    'retail_price'     => $postData['retail_price'] ?? 0,
                    'gift_points'      => $postData['gift_points'] ?? 0,
                    'manufacture_time' => $postData['manufacture_time'] ?? 0,
                    'batch_number'     => $postData['batch_number'] ?? '',
                ];

                $headBatch = $this->saveChannelGoodsBatch($mc['mc_id'], $headData, $batchArr);
                // 队首 g_id 跟 postData 不一致时，更新 postData 的商品信息
                if (isset($headBatch['g_id']) && $headBatch['g_id'] != ($postData['g_id'] ?? 0)) {
                    $postData['g_id'] = $headBatch['g_id'];
                    $postData['mg_id'] = $this->getMachineGoodsValue(['m_id' => $mc['m_id'], 'g_id' => $headBatch['g_id']], 'mg_id') ?? 0;
                    // 队首的 stock/frozen_stock/retail_price/gift_points 以批次表为准
                    $postData['stock']          = $headBatch['stock'];
                    $postData['frozen_stock']   = $headBatch['frozen_stock'];
                    $postData['retail_price']   = $headBatch['retail_price'];
                    $postData['gift_points']    = $headBatch['gift_points'];
                    $postData['is_multi_goods'] = 1;
                }
            }
            // ========== 多商品批次处理结束 ==========

            $newGId = isset($postData['g_id']) ? intval($postData['g_id']) : null;
            $oldGId = intval($mc['g_id'] ?? 0);
            $isChangingGoods = $newGId !== null && $newGId !== $oldGId;

            // 更换或清空货道商品时，先记录旧货架商品下货。
            if ($isChangingGoods && $oldGId > 0) {
                $insertGc = array_merge($insertGChange,[
                    "change_value" => $mc['stock'] ?? 0,
                    "type" => 7,
                    "desc" => $this->lang("goodsChange.backstage_exchange_mc_under_old"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);
            }

            if ($newGId !== null && $newGId > 0 && $isChangingGoods) {
                $goods = $this->getGoodsFind(['g_id' => $newGId],"g_id,g_name,gc_id,gc_name,pic,sku,bar_code");
                if (!$goods) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VGoods.goods_no_data"));
                }
                $goods = obj2arr($goods);
                if (!isset($postData['stock'])) {
                    $postData['stock'] = 0;
                }
                $postData['out_fail_stock'] = 0;
                if (!isset($postData['mg_id'])) {
                    $postData['mg_id'] = $this->getMachineGoodsValue(['m_id' => $mc['m_id'], 'g_id' => $newGId], 'mg_id') ?? 0;
                }
                $postData = array_merge($postData, $goods);

                $insertGc = array_merge($insertGChange,[
                    "mg_id" => $postData['mg_id'] ?? 0,
                    "g_id" => $goods['g_id'],
                    "g_name" => $goods['g_name'],
                    "gc_id" => $goods['gc_id'],
                    "gc_name" => $goods['gc_name'],
                    "pic" => $goods['pic'],
                    "sku" => $goods['sku'],
                    "bar_code" => $goods['bar_code'],
                    "change_value" => $postData['stock'] ?? 0,
                    "type" => 6,
                    "desc" => $this->lang("goodsChange.backstage_exchange_mc_display_new"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);
            } elseif ($newGId === 0 && $isChangingGoods) {
                $postData = array_merge($postData, [
                    'mg_id' => 0,
                    'g_name' => '',
                    'gc_id' => 0,
                    'gc_name' => '',
                    'pic' => '',
                    'sku' => '',
                    'bar_code' => '',
                    'stock' => 0,
                    'out_fail_stock' => 0,
                ]);
            }

            if (!$isChangingGoods && isset($postData['status']) && intval($postData['status']) === 1 && intval($mc['status']) === 3) {
                $postData = $this->mergeOutFailStockOnBadRecover($mc, $postData);
            }

            // 非换货库存调整也要记录，包括把库存调成0的后台下架退货。
            if (!$isChangingGoods && array_key_exists('stock', $postData) && bccomp((string)$mc['stock'], (string)$postData['stock'], 3) !== 0) {
                $changeValue = bcsub((string)$postData['stock'], (string)$mc['stock'], 3);
                if (bccomp($changeValue, '0', 3) !== 0) {
                    $insertGc = array_merge($insertGChange, [
                        "change_value" => $changeValue,
                        "type" => bccomp($changeValue, '0', 3) > 0 ? 6 : 7,
                        "desc" => bccomp($changeValue, '0', 3) > 0 ? $this->lang("goodsChange.backstage_rep_mc_inc_stock"): $this->lang("goodsChange.backstage_rep_mc_dec_stock"),
                        "position" => 1,
                    ]);
                    $this->addGoodsChange($insertGc);
                }
            }

            // bad状态变化（后台BAD 8，后台恢复BAD 9），变化数量为当前货架库存值。
            if (isset($postData['status']) && $postData['status'] != $mc['status'] && in_array($postData['status'],[1,3])) {
                $insertGc = array_merge($insertGChange, [
                    "change_value" => $mc['stock'],
                    "type" => $postData['status'] == 3 ? 8 : 9,
                    "desc" => $postData['status'] == 3 ? $this->lang("goodsChange.backstage_mc_bad") : $this->lang("goodsChange.backstage_mc_not_bad"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);
            }

            if (!empty($postData['manufacture_time'])) {
                $exp_arr = explode(" ",$postData['manufacture_time']);
                $postData['manufacture_time'] = strtotime($exp_arr[0] . ' 23:59:59');
            }
            //如果有传入生产日期，expire_time根据生产日期和商品表的保质期自动计算得出
            if (isset($postData['manufacture_time']) && $postData['manufacture_time'] > 0 && isset($postData['g_id']) && $postData['g_id'] > 0) {
                $shelfLife = $this->getGoodsValue(['g_id' => $postData['g_id']], 'sell_by_date');
                if ($shelfLife) {
                    $postData['expire_time'] = $postData['manufacture_time'] + $shelfLife * 86400;
                } else {
                    $postData['expire_time'] = 0;
                }
            }

            $result = $this->updateMachineChannel($postData);
            if (!$result) {
                $this->rollbackTrans();
                return $this->r(100,$this->lang('action_fail'));
            }

            $this->commitTrans();
            // 同步同设备同商品其他货道的售价
            $syncGid = $postData['g_id'] ?? $mc['g_id'];
            if ($syncGid > 0 && isset($postData['retail_price'])) {
                $otherChannels = $this->getMachineChannelList([
                    'm_id' => $mc['m_id'],
                    'g_id' => $syncGid,
                    ['mc_id', '<>', $mc['mc_id']],
                    ['status', '<>', 2],
                ], 0, 'mc_id,machine_id,channel_position,retail_price');
                if ($otherChannels) {
                    $otherChannels = $otherChannels->toArray();
                    foreach ($otherChannels as $ch) {
                        if ($ch['retail_price'] != $postData['retail_price']) {
                            $this->updateMachineChannel(['retail_price' => $postData['retail_price']], ['mc_id' => $ch['mc_id']]);
                            if ($ch['channel_position'] != 3) {
                                $this->sendToMachine(['machine_id' => $ch['machine_id']], 'updateMc', ['mc_id' => $ch['mc_id']]);
                            }
                        }
                    }
                }
            }
            // 发送触发货道更新数据,如果是边柜货道不发送
            if ($mc['channel_position'] != 3) {
                $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMc',['mc_id' => $mc['mc_id']]);
            }
            return $this->r(200,$this->lang("action_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function lockPrice($postData)
    {
        if (!isset($postData['m_id']) || !$postData['m_id']) return $this->r(100,$this->lang("VMachineChannel.m_id_require"));
        if (!isset($postData['update_price']) || !$postData['update_price'] || !in_array($postData['update_price'],[1,2]))
            return $this->r(100,$this->lang("VMachineChannel.update_price_error"));
        // 解锁，同步设备商品库或核心商品库价格
        if ($postData['update_price'] == 2) {
            $mc = $this->getMachineChannelList(['m_id' => $postData['m_id']],0,'update_price,cost_price,market_price,retail_price,mg_id,g_id,mc_id,machine_id');
            if ($mc) {
                $mc = $mc->toArray();
                foreach ($mc as $key => $value) {
                    $mg = $this->getMachineGoodsFind(['mg_id' => $value['mg_id']],'cost_price,market_price,retail_price');
                    if ($mg) {
                        $mg = $mg->toArray();
                        $update = $mg;
                        $update['mc_id'] = $value['mc_id'];
                        $update['update_price'] = $postData['update_price'];
                    } else {
                        $goods = $this->getGoodsFind(['g_id' => $value['g_id']],'cost_price,market_price,retail_price');
                        $update = $goods;
                        $update['mc_id'] = $value['mc_id'];
                        $update['update_price'] = $postData['update_price'];
                    }
                    $this->updateMachineChannel($update);
                    // 发送触发货道更新数据
                    $this->sendToMachine(['machine_id' => $value['machine_id']],'updateMc',['mc_id' => $value['mc_id']]);
                }
            }
        }
        // 锁定货架价格
        if ($postData['update_price'] == 1) {
            $this->updateMachineChannel(['update_price' => $postData['update_price']], ['m_id' => $postData['m_id']]);
        }
        return $this->r(200,$this->lang("action_success"));
    }

    /**
     * 按SKU导出货道数据
     * @param $m_id
     * @return mixed
     */
    public function exportMcSku($m_id, $hasCostPriceAuth = true)
    {
        if (!$m_id) return $this->r(100,$this->lang("VMachineChannel.m_id_require"));
        $costPriceField = $hasCostPriceAuth ? 'cost_price' : '0 cost_price';
        $field = "machine_id,sku,g_name,GROUP_CONCAT(channel_code ORDER BY channel_code SEPARATOR ',') channel_code,count(mc_id) channel_num,sum(capacity) capacity,sum(stock) stock,sum(frozen_stock) frozen_stock,retail_price,{$costPriceField}";
        $list = $this->getMachineChannelList(['m_id' => $m_id],0,$field,"","","sku");
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $machine_name = $this->getMachineValue(['m_id' => $m_id],'machine_name');
                foreach ($list as $key => $value) {
                    $value['machine_name'] = $machine_name;
                    $list[$key] = $value;
                }
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "channel_code" => "槽位",
                    "channel_num" => "货道数量",
                    "capacity" => "最大数量",
                    "stock" => "当前数量",
                    "frozen_stock" => "预定数量",
                    "retail_price" => "售价",
                ];
                if ($hasCostPriceAuth) $title["cost_price"] = "成本价";
                $filename = "按SKU铺货计划-" . date("YmdHis");
                return $this->sendToExport("设备管理-设备货架", $filename, $title, $list);
            }
        }
        return $this->r(100,$this->lang("query_fail"));
    }

    /**
     * 导出货架列表
     * @param $m_id
     * @return array|\think\response\Json
     */
    public function exportMc($m_id, $hasCostPriceAuth = true)
    {
        $costPriceField = $hasCostPriceAuth ? 'cost_price' : '0 cost_price';
        $field = "machine_id,channel_code,sku,g_name,capacity,stock,frozen_stock,retail_price,{$costPriceField}";
        $list = $this->getMachineChannelList(['m_id' => $m_id],0,$field);
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $machine_name = "";
                foreach ($list as $key => $value) {
                    if (!$machine_name) $machine_name = $this->getMachineValue(['m_id' => $m_id],'machine_name');
                    $value['machine_name'] = $machine_name;
                    $list[$key] = $value;
                }
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "channel_code" => "槽位",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "capacity" => "最大数量",
                    "stock" => "当前数量",
                    "frozen_stock" => "预定数量",
                    "retail_price" => "售价",
                ];
                if ($hasCostPriceAuth) $title["cost_price"] = "成本价";
                $filename =  "货架铺货计划-" . date("YmdHis");
                return $this->sendToExport("设备管理-设备货架", $filename, $title, $list);
            }
        }
        return $this->r(100,$this->lang("query_fail"));
    }


    public function setMachineChannelGiftPoints($m_id, $integral_rate)
    {
        if (!$m_id) return $this->r(100,$this->lang("VMachineChannel.m_id_require"));
        if ($integral_rate <= 0) return $this->r(100,$this->lang("请设置正常积分比例"));
        $machine_channel_lists = $this->getMachineChannelList(['m_id' => $m_id])->toArray();
        foreach($machine_channel_lists as $v){
            $gift_points = bcmul($v['retail_price'], $integral_rate, 2);
            $flag[] = $this->updateMachineChannel(['gift_points' => $gift_points], ['mc_id' => $v['mc_id']]);
        }
        if ($this->checkFlag($flag)) {
            return $this->r(200,$this->lang("action_success"));
        }
        return $this->r(100,$this->lang('action_fail'));
    }

    public function getMChannelList($where,$pageNum = 0,$field = "",$order = "",$hasCostPriceAuth = true)
    {
        //先查询设备详情
        $machine = $this->getMachineFind($where,'m_id,machine_id,machine_name,ao_id,vending_machine_type');
        if (!$machine) return $this->r(100,$this->lang("VMachine.machine_no_data"));
        if (!$hasCostPriceAuth) {
            if ($field === '' || $field === '*') {
                $field = '*,0 cost_price';
            } elseif (strpos($field, 'cost_price') !== false) {
                $field = str_replace('cost_price', '0 cost_price', $field);
            } else {
                $field .= ',0 cost_price';
            }
        }
        //把货道的channel_position设置成设备相同的vending_machine_type
        $list = $this->getMachineChannelList($where,$pageNum,$field,$order);
        $list = $list->toArray();
        foreach ($list as $key => $value) {
            $value['manufacture_time'] = $value['manufacture_time'] ? date("Y-m-d", $value['manufacture_time']) : '';
            $value['gift_points'] = round($value['gift_points']);
            $list[$key] = $value;
        }
        // foreach ($list as $key => &$value) {
        //     if (!isset($value['channel_code'])) {
        //         continue;
        //     }
        //     $channelCode = strval($value['channel_code']);
        //     if (strpos($channelCode, '02') === 0) {
        //         $value['channel_position'] = 3;
        //     } elseif (strpos($channelCode, '01') === 0) {
        //         $value['channel_position'] = 2;
        //     }
        // }
        return $this->rQ($list);
    }

    
    /**
     * 批量修改货道信息
     * @param $postData
     * @return array|string
     */
    public function batchUpdateMc($postData, $where = [])
    {
        //先查询是否有这台设备的权限
        $machine = $this->getMachineFind($where,'m_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->r(100,$this->lang("VMachine.machine_no_data"));
        $mc_ids = $postData['mc_ids'] ?? '';
        $mc_ids = explode(",",$mc_ids);

        if (!$mc_ids) return $this->r(100, $this->lang("VMachineChannel.mc_id_require"));

        $updateData = [];
        if(isset($postData['retail_price'])){
            $updateData['retail_price'] = $postData['retail_price'] < 0 ? 0 : $postData['retail_price'];
        }
        if(isset($postData['gift_points'])){
            $updateData['gift_points'] = $postData['gift_points'] < 0 ? 0 : $postData['gift_points'];
        }
        if(isset($postData['stock_warning'])){
            $updateData['stock_warning'] = $postData['stock_warning'] < 0 ? 0 : $postData['stock_warning'];
        }
        if(!empty($postData['expire_time'])){
            $exp_arr = explode(" ",$postData['expire_time']);
            $updateData['expire_time'] = strtotime($exp_arr[0] . ' 23:59:59');
        }
        if (!$updateData) return $this->r(100, $this->lang("action_fail"));

        try {
            foreach ($mc_ids as $mc_id) {
                $mc = $this->getMachineChannelFind(['mc_id' => $mc_id,'m_id' => $machine['m_id']], 'mc_id,retail_price,gift_points,stock_warning,old_retail_price,old_gift_points,old_stock_warning,machine_id');
                if (!$mc) continue;

                $saveData = $updateData;
                // 只要传了这个字段，就要保存当前值为旧值
                if (isset($updateData['retail_price'])) {
                    $saveData['old_retail_price'] = $mc['retail_price'];
                }
                if (isset($updateData['gift_points'])) {
                    $saveData['old_gift_points'] = $mc['gift_points'];
                }
                if (isset($updateData['stock_warning'])) {
                    $saveData['old_stock_warning'] = $mc['stock_warning'];
                }
                $this->updateMachineChannel($saveData, ['mc_id' => $mc_id]);
                // 发送触发货道更新数据
                $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => (int)$mc_id]);
            }
            return $this->r(200, $this->lang("action_success"));
        } catch (\Exception $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * 批量还原货道信息
     * @param $postData
     * @return array|string
     */
    public function batchRestoreMc($postData,$where = [])
    {
        //先查询是否有这台设备的权限
        $machine = $this->getMachineFind($where,'m_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->r(100,$this->lang("VMachine.machine_no_data"));
        $mc_ids = $postData['mc_ids'] ?? [];
        $fields = $postData['fields'] ?? []; // ['retail_price', 'gift_points', 'stock_warning']
        if (!$mc_ids || !$fields) return $this->r(100, $this->lang("VMachineChannel.mc_id_require"));

        $this->startTrans();
        try {
            foreach ($mc_ids as $mc_id) {
                $mc = $this->getMachineChannelFind(['mc_id' => $mc_id,'m_id' => $machine['m_id']], 'mc_id,old_retail_price,old_gift_points,old_stock_warning,machine_id');
                if (!$mc) continue;

                $restoreData = [];
                if (in_array('retail_price', $fields) && $mc['old_retail_price'] != -1) {
                    $restoreData['retail_price'] = $mc['old_retail_price'];
                    $restoreData['old_retail_price'] = -1;
                }
                if (in_array('gift_points', $fields) && $mc['old_gift_points'] != -1) {
                    $restoreData['gift_points'] = $mc['old_gift_points'];
                    $restoreData['old_gift_points'] = -1;
                }
                if (in_array('stock_warning', $fields) && $mc['old_stock_warning'] != -1) {
                    $restoreData['stock_warning'] = $mc['old_stock_warning'];
                    $restoreData['old_stock_warning'] = -1;
                }

                if ($restoreData) {
                    $this->updateMachineChannel($restoreData, ['mc_id' => $mc_id]);
                    // 发送触发货道更新数据
                    $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => (int)$mc_id]);
                }
            }
            $this->commitTrans();
            return $this->r(200, $this->lang("action_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * 远程下架货道商品进行回收
     * @param array $postData
     * @return array|string
     */
    public function remoteRemoval($postData)
    {
        $mc = $this->getMachineChannelFind(
            ['mc_id' => $postData['mc_id']],
            'mc_id,m_id,machine_id,channel_code,mg_id,g_id,sku,stock'
        );
        if (!$mc) {
            return $this->r(100, $this->lang("VMachineChannel.mc_data_empty"));
        }
        $mc = $mc->toArray();
        if (intval($mc['g_id']) <= 0) {
            return $this->r(100, $this->lang("VMachineChannel.mc_empty_goods"));
        }

        $lastLog = $this->getRemoteRemovalLogFind(
            [
                ['m_id', '=', $mc['m_id']],
                ['created_at', '>=', time() - 600],
            ],
            'id,created_at',
            'id desc'
        );
        if ($lastLog) {
            return $this->r(100, '同一台设备10分钟内只能执行一次远程下架回收');
        }

        $send = $this->sendToMachine(
            ['machine_id' => $mc['machine_id']],
            'remoteRemoval',
            [
                'mc_id' => intval($mc['mc_id']),
                'channel_code' => $mc['channel_code'],
            ]
        );

        if (!$send || is_string($send)) {
            return $this->r(100, is_string($send) ? $send : $this->lang('action_fail'));
        }

        if (is_array($send) && isset($send['state']) && intval($send['state']) != 200) {
            return $this->r(100, $send['msg'] ?? $this->lang('action_fail'));
        }

        $insert = [
            'm_id' => $mc['m_id'],
            'machine_id' => $mc['machine_id'],
            'mc_id' => $mc['mc_id'],
            'g_id' => $mc['g_id'],
            'sku' => $mc['sku'] ?? '',
            'total_count' => max(intval($mc['stock']), 0),
            'success_count' => 0,
            'fail_count' => 0,
            'remark' => '下发remoteRemoval指令',
            'created_at' => time(),
            'reported_at' => 0,
        ];
        $this->addRemoteRemovalLog($insert);

        return $this->r(200, $this->lang('action_success'), [
            'mc_id' => intval($mc['mc_id']),
            'channel_code' => $mc['channel_code'],
        ]);
    }

    public function getMcFind($where,$field = '*')
    {
        $mc = $this->getMachineChannelFind($where,$field);
        if (!$mc) return $this->r(100,$this->lang("VMachineChannel.mc_no_data"));
        $mc['manufacture_time'] = $mc['manufacture_time'] ? date("Y-m-d",$mc['manufacture_time']) : '';
        $mc['gift_points'] = round($mc['gift_points'] ?? 0);
        return $this->r(200,'success',$mc);
    }
}
