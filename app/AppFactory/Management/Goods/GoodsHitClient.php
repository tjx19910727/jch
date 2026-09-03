<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 17:32
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsBehaviorTrackingTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class GoodsHitClient extends ManagementClient
{
    use GoodsBehaviorTrackingTrait,GoodsHitTrait,SaleOrdersGoodsCountTrait;

    public function getTotalList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];
        $return = $this->rQ($this->getGoodsHitList($where,$pageNum,$field,$order,function ($item) {
            $item['hits'] = $this->getGoodsHitCount(['g_id' => $item['g_id']]);
            $item['saleNum'] = $this->getSaleOrdersGoodsCountSum(['g_id' => $item['g_id']],'totalQuantity');
            $item['conversion_rate'] = ($item['saleNum'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            return $item;
        },"g_id"));
        return $return;
    }

    /**
     * 获取互动报表列表，支持按商品或按设备汇总。
     *
     * 销量和转化率在分页前完成计算，确保排序针对全部结果，
     * 而不是只对当前页的数据进行排序。
     */
    public function getTotalListV2($where,$pageNum = 0,$groupType = 'goods',$sortName = '',$sortOrder = 'desc')
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];

        $groupType = $this->normalizeGoodsHitGroupType($groupType);
        $internalGroupType = $groupType === 'machine' ? 'device' : 'goods';
        $behaviorQuery = $this->buildVersionedGoodsHitQuery($where, $internalGroupType);

        $saleQuery = $this->buildGoodsHitSaleQuantityQuery($where, $internalGroupType);
        $saleSql = $saleQuery->buildSql(false);
        $metricQuery = Db::table($behaviorQuery->buildSql() . ' hit_stats')
            ->fieldRaw("hit_stats.*,IFNULL(({$saleSql}),0) saleNum");

        $query = Db::table($metricQuery->buildSql() . ' metric_stats')
            ->fieldRaw(
                'metric_stats.*,CASE WHEN metric_stats.hits > 0 AND metric_stats.saleNum > 0 '
                . 'THEN metric_stats.saleNum / metric_stats.hits * 100 ELSE 0 END conversion_rate_value'
            );
        $query->orderRaw($this->buildGoodsHitMetricOrder($groupType, $sortName, $sortOrder));

        if ($pageNum) {
            $list = $query->paginate($pageNum, false, ["query" => request()->param()]);
        } else {
            $list = $query->select();
        }

        if (method_exists($list, 'each')) {
            $list = $list->each(function ($item) {
                $item['saleNum'] = intval($item['saleNum'] ?? 0);
                $item['conversion_rate'] = ($item['saleNum'] > 0 && $item['hits'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
                unset($item['conversion_rate_value'], $item['goods_out_no']);
                return $item;
            });
        }

        return $this->rQ($list);
    }

    public function getHitList($where,$pageNum = 0,$field = "*",$order = "",$eachFun = "",$group = "")
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];
        return $this->r(200,$this->lang("query_success"),$this->getGoodsHitList($where,$pageNum,$field,$order,$eachFun,$group));
    }

    public function getHitListV2($where,$pageNum = 0,$field = "*",$order = "",$eachFun = "",$group = "")
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];
        $list = $this->getVersionedGoodsHitList($where,$pageNum,'machine',$order,function ($item) use ($where) {
            $item['saleNum'] = $this->getNetSaleQuantity($where, $item);
            $item['conversion_rate'] = ($item['saleNum'] > 0 && $item['hits'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            return $item;
        });
        return $this->r(200,$this->lang("query_success"),$list);
    }

    public function export($where,$eType = 1)
    {
        $field = "*";
        $order = "";
        $group = "";
        if ($eType == 1) {
            $group = "g_id";
            $field = "g_id,g_name,sku,gc_name,count(gh_id) hits";
        }
        if ($eType == 2) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) $where[] = ['m_id', 'in', $mIds];
            $field = 'machine_id,machine_name,g_name,gc_name,sku,g_id,count(gh_id) hits, date_format(create_date,"%Y-%m-%d") create_date';
            $group = "m_id,g_id,create_date";
        }
        $list = $this->getGoodsHitList($where,0,$field,$order,'',$group);
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $key => $item) {
                $item['saleNum'] = $this->getSaleOrdersGoodsCountSum(['g_id' => $item['g_id']],'totalQuantity');
                $item['conversion_rate'] = ($item['saleNum'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
                $list[$key] = $item;
            }
            if ($eType == 1) {
                $title = [
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "gc_name" => "品类",
                    "hits" => "点击数",
                    "saleNum" => "销量",
                    "conversion_rate" => "转化率",
                ];
                $filename = "互动报表(按商品)-" . date("Ymd");
            }
            if ($eType == 2) {
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "gc_name" => "品类",
                    "hits" => "点击数",
                    "saleNum" => "销量",
                    "conversion_rate" => "转化率",
                ];
                $filename = "互动报表(按设备)-" . date("Ymd");
            }
            return $this->sendToExport("统计报表-互动报表", $filename, $title, $list);
        }
        return $this->rFail();
    }

    public function exportV2($where,$eType = 1)
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];
        $groupType = $eType == 2 ? 'machine' : 'goods';
        $list = $this->getVersionedGoodsHitList($where,0,$groupType);
        if (!$list) return $this->rFail();
        $list = $list->toArray();
        foreach ($list as $key => $item) {
            $item['saleNum'] = $this->getNetSaleQuantity($where, $item);
            $item['conversion_rate'] = ($item['saleNum'] > 0 && $item['hits'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            $list[$key] = $item;
        }
        if ($eType == 1) {
            $title = [
                "sku" => "SKU",
                "g_name" => "商品名称",
                "gc_name" => "品类",
                "hits" => "点击数",
                "cart_add_count" => "加购件数",
                "retry_dispense_count" => "再次出货次数",
                "help_count" => "帮助点击数",
                "saleNum" => "销量",
                "conversion_rate" => "转化率",
            ];
            $filename = "互动报表(按商品)-" . date("Ymd");
        }
        if ($eType == 2) {
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "sku" => "SKU",
                "g_name" => "商品名称",
                "gc_name" => "品类",
                "hits" => "点击数",
                "cart_add_count" => "加购件数",
                "retry_dispense_count" => "再次出货次数",
                "help_count" => "帮助点击数",
                "saleNum" => "销量",
                "conversion_rate" => "转化率",
            ];
            $filename = "互动报表(按设备)-" . date("Ymd");
        }
        return $this->sendToExport("统计报表-互动报表", $filename, $title, $list);
    }

    /**
     * 在执行 MAX 聚合前统一两种商品来源的字符集和排序规则，
     * 避免不同部署环境中的字段排序规则不一致导致聚合失败。
     */
    protected function getGoodsBehaviorTrackingAggregateFields()
    {
        return "MAX(IF(gbt.is_online = 1, CONVERT(wg.g_name USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(g.g_name USING utf8mb4) COLLATE utf8mb4_unicode_ci)) g_name,"
            . "MAX(IF(gbt.is_online = 1, CONVERT(wg.out_no USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(g.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci)) sku,"
            . "MAX(IF(gbt.is_online = 1, CONVERT(wg.gc_name USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(g.gc_name USING utf8mb4) COLLATE utf8mb4_unicode_ci)) gc_name,"
            . "MAX(IF(gbt.is_online = 1, CONVERT(wg.out_no USING utf8mb4) COLLATE utf8mb4_unicode_ci, NULL)) goods_out_no";
    }

    protected function getVersionedGoodsHitList($where,$pageNum = 0,$groupType = 'goods',$order = '',$eachFun = '')
    {
        $query = $this->buildVersionedGoodsHitQuery($where, $groupType);

        if ($order) $query->order($order);

        if (!$pageNum) {
            $list = $query->select();
        } else {
            $list = $query->paginate($pageNum, false, ["query" => request()->param()]);
        }
        if ($eachFun && is_callable($eachFun) && method_exists($list, 'each')) {
            $list = $list->each($eachFun);
        }
        return $list;
    }

    /**
     * 构建兼容新旧版本行为数据的聚合查询，但不立即执行。
     */
    protected function buildVersionedGoodsHitQuery($where,$groupType = 'goods')
    {
        $cutoverSql = $this->getBehaviorVersionCutoverSubQuery();
        $oldQuery = Db::name('goods_hit')->alias('gh')
            ->leftJoin([$cutoverSql => 'bvc'], 'bvc.m_id = gh.m_id')
            ->where($this->formatOldGoodsHitWhere($where))
            ->whereRaw('(bvc.switch_time IS NULL OR gh.create_time < bvc.switch_time)')
            ->field($this->getVersionedGoodsHitSourceFields('old', $groupType))
            ->group($this->getVersionedGoodsHitSourceGroup('old', $groupType));

        $trackingWhere = $this->formatGoodsBehaviorTrackingWhere($where);
        $newQuery = Db::name('goods_behavior_tracking')->alias('gbt')
            ->leftJoin('goods g', 'gbt.is_online = 2 AND g.g_id = gbt.goods_id')
            ->leftJoin('wc_goods_local wg', 'gbt.is_online = 1 AND wg.id = gbt.goods_id')
            ->leftJoin('machine m', 'm.m_id = gbt.m_id')
            ->leftJoin([$cutoverSql => 'bvc'], 'bvc.m_id = gbt.m_id')
            ->where($trackingWhere)
            ->whereRaw(
                'bvc.switch_time IS NOT NULL '
                . 'AND gbt.device_created_at >= bvc.switch_time'
            )
            ->field($this->getVersionedGoodsHitSourceFields('new', $groupType))
            ->group($this->getVersionedGoodsHitSourceGroup('new', $groupType));

        $oldQuery->unionAll($newQuery->buildSql(false));
        $query = Db::table($oldQuery->buildSql() . ' behavior_stats')
            ->field($this->getVersionedGoodsHitAggregateFields($groupType))
            ->group($this->getVersionedGoodsHitAggregateGroup($groupType));
        return $query;
    }

    protected function getVersionedGoodsHitSourceFields($source,$groupType)
    {
        $isDeviceGroup = $groupType === 'device';
        $isMachineGroup = in_array($groupType, ['machine', 'machine_date', 'device'], true);
        $isDateGroup = $groupType === 'machine_date';

        if ($source === 'old') {
            $machineFields = $isMachineGroup
                ? "gh.m_id,MAX(CONVERT(gh.machine_id USING utf8mb4) COLLATE utf8mb4_unicode_ci) machine_id,MAX(CONVERT(gh.machine_name USING utf8mb4) COLLATE utf8mb4_unicode_ci) machine_name"
                : "0 m_id,'' machine_id,'' machine_name";

            if ($isDeviceGroup) {
                return "{$machineFields},"
                    . "FROM_UNIXTIME(MAX(gh.create_time),'%Y-%m-%d %H:%i:%s') create_time,"
                    . "COUNT(gh.gh_id) hits,0 cart_add_count,0 retry_dispense_count,0 help_count";
            }

            $createDate = $isDateGroup
                ? "DATE(FROM_UNIXTIME(gh.create_time)) create_date"
                : "NULL create_date";

            return "gh.g_id,2 is_online,{$machineFields},"
                . "MAX(CONVERT(gh.g_name USING utf8mb4) COLLATE utf8mb4_unicode_ci) g_name,"
                . "MAX(CONVERT(gh.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci) sku,"
                . "MAX(CONVERT(gh.gc_name USING utf8mb4) COLLATE utf8mb4_unicode_ci) gc_name,"
                . "CONVERT('' USING utf8mb4) COLLATE utf8mb4_unicode_ci goods_out_no,"
                . "FROM_UNIXTIME(MAX(gh.create_time),'%Y-%m-%d %H:%i:%s') create_time,"
                . "{$createDate},COUNT(gh.gh_id) hits,0 cart_add_count,0 retry_dispense_count,0 help_count";
        }

        $machineFields = $isMachineGroup
            ? "gbt.m_id,MAX(CONVERT(gbt.machine_id USING utf8mb4) COLLATE utf8mb4_unicode_ci) machine_id,MAX(CONVERT(m.machine_name USING utf8mb4) COLLATE utf8mb4_unicode_ci) machine_name"
            : "0 m_id,'' machine_id,'' machine_name";

        if ($isDeviceGroup) {
            return "{$machineFields},"
                . "DATE_FORMAT(MAX(gbt.updated_at),'%Y-%m-%d %H:%i:%s') create_time,"
                . "SUM(gbt.click_count) hits,SUM(gbt.cart_add_count) cart_add_count,"
                . "SUM(gbt.retry_dispense_count) retry_dispense_count,SUM(gbt.help_count) help_count";
        }

        $createDate = $isDateGroup ? 'gbt.report_date create_date' : 'NULL create_date';
        $goodsFields = $this->getGoodsBehaviorTrackingAggregateFields();

        return "gbt.goods_id g_id,gbt.is_online,{$machineFields},{$goodsFields},"
            . "DATE_FORMAT(MAX(gbt.updated_at),'%Y-%m-%d %H:%i:%s') create_time,{$createDate},"
            . "SUM(gbt.click_count) hits,SUM(gbt.cart_add_count) cart_add_count,"
            . "SUM(gbt.retry_dispense_count) retry_dispense_count,SUM(gbt.help_count) help_count";
    }

    protected function getVersionedGoodsHitSourceGroup($source,$groupType)
    {
        $prefix = $source === 'old' ? 'gh' : 'gbt';
        if ($groupType === 'device') return "{$prefix}.m_id";

        $goodsId = $source === 'old' ? 'g_id' : 'goods_id';
        $group = "{$prefix}.{$goodsId}";
        if ($source === 'new') $group .= ',gbt.is_online';
        if (in_array($groupType, ['machine', 'machine_date'], true)) $group .= ",{$prefix}.m_id";
        if ($groupType === 'machine_date') {
            $group .= $source === 'old' ? ',DATE(FROM_UNIXTIME(gh.create_time))' : ',gbt.report_date';
        }
        return $group;
    }

    protected function getVersionedGoodsHitAggregateFields($groupType)
    {
        if ($groupType === 'device') {
            return "behavior_stats.m_id,MAX(behavior_stats.machine_id) machine_id,"
                . "MAX(behavior_stats.machine_name) machine_name,"
                . "MAX(behavior_stats.create_time) create_time,"
                . "SUM(behavior_stats.hits) hits,"
                . "SUM(behavior_stats.cart_add_count) cart_add_count,"
                . "SUM(behavior_stats.retry_dispense_count) retry_dispense_count,"
                . "SUM(behavior_stats.help_count) help_count";
        }

        $field = "behavior_stats.g_id,behavior_stats.is_online,"
            . "MAX(behavior_stats.goods_out_no) goods_out_no,"
            . "MAX(behavior_stats.g_name) g_name,MAX(behavior_stats.sku) sku,"
            . "MAX(behavior_stats.gc_name) gc_name,"
            . "SUM(behavior_stats.hits) hits,"
            . "SUM(behavior_stats.cart_add_count) cart_add_count,"
            . "SUM(behavior_stats.retry_dispense_count) retry_dispense_count,"
            . "SUM(behavior_stats.help_count) help_count";

        if (in_array($groupType, ['machine', 'machine_date'], true)) {
            $field .= ",behavior_stats.m_id,MAX(behavior_stats.machine_id) machine_id,"
                . "MAX(behavior_stats.machine_name) machine_name,"
                . "MAX(behavior_stats.create_time) create_time";
        }
        if ($groupType === 'machine_date') {
            $field .= ',behavior_stats.create_date';
        }
        return $field;
    }

    protected function getVersionedGoodsHitAggregateGroup($groupType)
    {
        if ($groupType === 'device') return 'behavior_stats.m_id';

        $group = 'behavior_stats.g_id,behavior_stats.is_online';
        if (in_array($groupType, ['machine', 'machine_date'], true)) {
            $group .= ',behavior_stats.m_id';
        }
        if ($groupType === 'machine_date') {
            $group .= ',behavior_stats.create_date';
        }
        return $group;
    }

    /**
     * 统一外部传入的分组类型，并兼容部分旧管理页面使用的数字类型。
     */
    protected function normalizeGoodsHitGroupType($groupType)
    {
        $groupType = strtolower(trim(strval($groupType)));
        if (in_array($groupType, ['machine', 'device', '2'], true)) return 'machine';
        return 'goods';
    }

    /**
     * 根据白名单生成排序语句，禁止前端参数直接进入 ORDER BY。
     * 同时追加固定的次级排序，避免指标相同时翻页数据顺序不稳定。
     */
    protected function buildGoodsHitMetricOrder($groupType,$sortName,$sortOrder)
    {
        $sortName = strtolower(trim(strval($sortName)));
        $sortOrder = strtolower(trim(strval($sortOrder))) === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'hits' => 'hits',
            'click_count' => 'hits',
            'sale_num' => 'saleNum',
            'salenum' => 'saleNum',
            'conversion_rate' => 'conversion_rate_value',
        ];
        $stableOrder = $groupType === 'machine' ? 'm_id desc' : 'g_id desc,is_online desc';

        if (!isset($sortMap[$sortName])) return $stableOrder;
        return $sortMap[$sortName] . " {$sortOrder},{$stableOrder}";
    }

    /**
     * 构建销量关联聚合子查询。
     *
     * 销量作为列表查询的一部分在分页前完成计算，既能参与全量排序，
     * 又能避免原来通过回调逐行查询销量产生的 N+1 查询问题。
     */
    protected function buildGoodsHitSaleQuantityQuery($where,$groupType)
    {
        $query = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where('so.pay_status', 3);

        if ($groupType === 'device') {
            $query->whereRaw('so.m_id = hit_stats.m_id');
        } else {
            $query->whereRaw(
                "((hit_stats.is_online = 1 AND hit_stats.goods_out_no <> '' "
                . "AND JSON_SEARCH(IF(JSON_VALID(sod.wc_order_no), sod.wc_order_no, JSON_OBJECT()), "
                . "'one', hit_stats.goods_out_no, NULL, '$.*.out_no') IS NOT NULL) "
                . "OR (hit_stats.is_online <> 1 AND sod.g_id = hit_stats.g_id))"
            );
        }

        $this->applyGoodsHitSaleWhere($query, $where);
        return $query->fieldRaw(
            'IFNULL(SUM(IFNULL(sod.quantity,0) - IFNULL(sod.refund_quantity,0)),0)'
        );
    }

    /**
     * 将互动报表的公共筛选范围转换为订单表字段，并应用到销量查询。
     */
    protected function applyGoodsHitSaleWhere($query,$where)
    {
        foreach ($where as $key => $value) {
            if (!is_array($value)) {
                if ($key == 'ao_id') $query->where('so.ao_id', $value);
                continue;
            }

            $field = $value[0] ?? '';
            $op = $value[1] ?? '=';
            $val = $value[2] ?? '';
            if ($field == 'm_id') $query->where('so.m_id', $op, $val);
            if ($field == 'machine_id') $query->where('so.machine_id', $op, $val);
            if ($field == 'create_time') $query->where('so.create_time', $op, $val);
            if ($field == 'ao_id') $query->where('so.ao_id', $op, $val);
        }
        return $query;
    }

    protected function formatOldGoodsHitWhere($where)
    {
        $oldWhere = [];
        foreach ($where as $key => $value) {
            if (!is_array($value)) {
                $oldWhere[$key === 'ao_id' ? 'gh.ao_id' : $key] = $value;
                continue;
            }

            $field = $value[0] ?? '';
            $fieldMap = [
                'g_id' => 'gh.g_id',
                'm_id' => 'gh.m_id',
                'machine_id' => 'gh.machine_id',
                'sku' => 'gh.sku',
                'create_time' => 'gh.create_time',
                'ao_id' => 'gh.ao_id',
            ];
            if (isset($fieldMap[$field])) $value[0] = $fieldMap[$field];
            $oldWhere[] = $value;
        }
        return $oldWhere;
    }

    protected function formatGoodsBehaviorTrackingWhere($where)
    {
        $newWhere = [];
        foreach ($where as $key => $value) {
            if (!is_array($value)) {
                if ($key == 'ao_id') {
                    $newWhere['m.ao_id'] = $value;
                } else {
                    $newWhere[$key] = $value;
                }
                continue;
            }

            $field = $value[0] ?? '';
            if ($field == 'g_id') $value[0] = 'gbt.goods_id';
            if ($field == 'm_id') $value[0] = 'gbt.m_id';
            if ($field == 'machine_id') $value[0] = 'gbt.machine_id';
            if ($field == 'sku') {
                $value[0] = Db::raw(
                    'IF('
                    . 'gbt.is_online = 1,'
                    . 'CONVERT(wg.out_no USING utf8mb4) COLLATE utf8mb4_unicode_ci,'
                    . 'CONVERT(g.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci'
                    . ')'
                );
            }
            if ($field == 'create_time') {
                $value[0] = 'gbt.device_created_at';
            }
            if ($field == 'ao_id') $value[0] = 'm.ao_id';
            $newWhere[] = $value;
        }
        return $newWhere;
    }

    protected function formatGoodsBehaviorTrackingOrder($order)
    {
        return str_replace(['g_id', 'm_id', 'create_time'], ['gbt.goods_id', 'gbt.m_id', 'gbt.device_created_at'], $order);
    }

    protected function getNetSaleQuantity($where, $item = [])
    {
        $gId = intval($item['g_id'] ?? 0);
        if (!$gId) return 0;

        $query = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where('so.pay_status', 3);

        if (intval($item['is_online'] ?? 2) === 1) {
            $outNo = trim(strval($item['goods_out_no'] ?? ''));
            if ($outNo === '') return 0;
            $query->whereRaw(
                "JSON_SEARCH(IF(JSON_VALID(sod.wc_order_no), sod.wc_order_no, JSON_OBJECT()), 'one', ?, NULL, '$.*.out_no') IS NOT NULL",
                [$outNo]
            );
        } else {
            $query->where('sod.g_id', $gId);
        }

        if (!empty($item['m_id'])) {
            $query->where('so.m_id', intval($item['m_id']));
        }
        if (!empty($item['create_date'])) {
            $query->where('so.create_time', 'between', [
                strtotime($item['create_date'] . ' 00:00:00'),
                strtotime($item['create_date'] . ' 23:59:59'),
            ]);
        }

        foreach ($where as $key => $value) {
            if (!is_array($value)) {
                if ($key == 'ao_id') $query->where('so.ao_id', $value);
                continue;
            }
            $field = $value[0] ?? '';
            $op = $value[1] ?? '=';
            $val = $value[2] ?? '';
            if ($field == 'm_id') $query->where('so.m_id', $op, $val);
            if ($field == 'machine_id') $query->where('so.machine_id', $op, $val);
            if ($field == 'create_time') $query->where('so.create_time', $op, $val);
            if ($field == 'ao_id') $query->where('so.ao_id', $op, $val);
        }

        $row = $query->fieldRaw('IFNULL(SUM(IFNULL(sod.quantity,0) - IFNULL(sod.refund_quantity,0)),0) saleNum')->find();
        return intval($row['saleNum'] ?? 0);
    }
}
