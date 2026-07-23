<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 17:32
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class GoodsHitClient extends ManagementClient
{
    use GoodsHitTrait,SaleOrdersGoodsCountTrait;

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

    public function getTotalListV2($where,$pageNum = 0,$field = "*",$order = "")
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];
        $trackingWhere = $this->formatGoodsBehaviorTrackingWhere($where);
        $goodsFields = $this->getGoodsBehaviorTrackingAggregateFields();
        $field = "gbt.goods_id g_id,gbt.is_online,{$goodsFields},SUM(gbt.click_count) hits,SUM(gbt.cart_add_count) cart_add_count,SUM(gbt.retry_dispense_count) retry_dispense_count,SUM(gbt.help_count) help_count";
        $return = $this->rQ($this->getGoodsBehaviorTrackingHitList($trackingWhere,$pageNum,$field,'gbt.goods_id desc',function ($item) use ($where) {
            $item['saleNum'] = $this->getNetSaleQuantity($where, $item);
            $item['conversion_rate'] = ($item['saleNum'] > 0 && $item['hits'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            return $item;
        },"gbt.goods_id,gbt.is_online"));
        return $return;
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
        $trackingWhere = $this->formatGoodsBehaviorTrackingWhere($where);
        $goodsFields = $this->getGoodsBehaviorTrackingAggregateFields();
        $field = "gbt.goods_id g_id,gbt.is_online,gbt.m_id,gbt.machine_id,{$goodsFields},MAX(m.machine_name) machine_name,MAX(gbt.updated_at) create_time,SUM(gbt.click_count) hits,SUM(gbt.cart_add_count) cart_add_count,SUM(gbt.retry_dispense_count) retry_dispense_count,SUM(gbt.help_count) help_count";
        $list = $this->getGoodsBehaviorTrackingHitList($trackingWhere,$pageNum,$field,$order,function ($item) use ($where) {
            $item['saleNum'] = $this->getNetSaleQuantity($where, $item);
            $item['conversion_rate'] = ($item['saleNum'] > 0 && $item['hits'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            return $item;
        },"gbt.goods_id,gbt.is_online,gbt.m_id");
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
        $trackingWhere = $this->formatGoodsBehaviorTrackingWhere($where);
        $goodsFields = $this->getGoodsBehaviorTrackingAggregateFields();
        $field = "gbt.goods_id g_id,gbt.is_online,{$goodsFields},SUM(gbt.click_count) hits,SUM(gbt.cart_add_count) cart_add_count,SUM(gbt.retry_dispense_count) retry_dispense_count,SUM(gbt.help_count) help_count";
        $group = "gbt.goods_id,gbt.is_online";
        if ($eType == 2) {
            $field = "gbt.machine_id,gbt.is_online,{$goodsFields},MAX(m.machine_name) machine_name,gbt.goods_id g_id,SUM(gbt.click_count) hits,SUM(gbt.cart_add_count) cart_add_count,SUM(gbt.retry_dispense_count) retry_dispense_count,SUM(gbt.help_count) help_count,gbt.report_date create_date,gbt.m_id";
            $group = "gbt.m_id,gbt.goods_id,gbt.is_online,gbt.report_date";
        }
        $list = $this->getGoodsBehaviorTrackingHitList($trackingWhere,0,$field,'','',$group);
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
     * Normalize both goods sources before MAX so deployments with different
     * column collations can be aggregated safely.
     */
    protected function getGoodsBehaviorTrackingAggregateFields()
    {
        return "MAX(IF(gbt.is_online = 1, CONVERT(wg.out_no USING utf8mb4) COLLATE utf8mb4_unicode_ci, NULL)) goods_out_no,MAX(IF(gbt.is_online = 1, CONVERT(wg.g_name USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(g.g_name USING utf8mb4) COLLATE utf8mb4_unicode_ci)) g_name,MAX(IF(gbt.is_online = 1, CONVERT(wg.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(g.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci)) sku,MAX(IF(gbt.is_online = 1, CONVERT(wg.gc_name USING utf8mb4) COLLATE utf8mb4_unicode_ci, CONVERT(g.gc_name USING utf8mb4) COLLATE utf8mb4_unicode_ci)) gc_name";
    }

    protected function getGoodsBehaviorTrackingHitList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = "")
    {
        $query = Db::name('goods_behavior_tracking')->alias('gbt')
            ->leftJoin('goods g', 'gbt.is_online = 2 AND g.g_id = gbt.goods_id')
            ->leftJoin('wc_goods_local wg', 'gbt.is_online = 1 AND wg.id = gbt.goods_id')
            ->leftJoin('machine m', 'm.m_id = gbt.m_id')
            ->where($where)
            ->field($field);

        if ($group) $query->group($group);
        if ($order) $query->order($this->formatGoodsBehaviorTrackingOrder($order));

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
            if ($field == 'sku') $value[0] = 'g.sku';
            if ($field == 'create_time') {
                $value[0] = 'gbt.report_date';
                if (($value[1] ?? '') == 'between' && is_array($value[2] ?? null)) {
                    $value[2] = [
                        date('Y-m-d', intval($value[2][0])),
                        date('Y-m-d', intval($value[2][1])),
                    ];
                } elseif (isset($value[2]) && is_numeric($value[2])) {
                    $value[2] = date('Y-m-d', intval($value[2]));
                }
            }
            if ($field == 'ao_id') $value[0] = 'm.ao_id';
            $newWhere[] = $value;
        }
        return $newWhere;
    }

    protected function formatGoodsBehaviorTrackingOrder($order)
    {
        return str_replace(['g_id', 'm_id', 'create_time'], ['gbt.goods_id', 'gbt.m_id', 'gbt.report_date'], $order);
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
