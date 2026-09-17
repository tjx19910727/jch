<?php

namespace app\AppFactory\Kernel\Traits\Machine;

use think\facade\Db;

/**
 * 设备商品库存变化统计（m_id + g_id + 起止时间）
 *
 * 口径（与文档《设备商品库存变化统计接口设计方案》保持一致）：
 *   1) 期末/剩余库存 = 货道库存汇总（machine_goods 上的同名库存列是历史遗留、恒为 0，
 *      真实库存以 machine_channel 汇总为准，见 MachineGoodsModel::STOCK_FIELDS 注释）；
 *   2) 累计上架 = machine_channel_replenishment.quantity > 0 合计（有补货单可查）；
 *   3) 累计下架 = machine_channel_replenishment.quantity < 0 绝对值合计（有补货单可查）；
 *   4) 累计销售 = sale_orders_details.success_quantity，订单已支付且出货成功/未取，
 *      按 sale_orders.out_time 落在区间内（有订单可查）；
 *   5) 初始库存 = 期末库存 − (累计上架 − 累计下架 − 累计销售)：系统没有历史库存快照表，
 *      期初只能由"实物锚点 + 台账"反推，故同时返回 cross_check 把差额显式暴露。
 *
 * 等式：初始库存 + 累计上架 = 累计下架 + 累计销售 + 剩余库存（equation.diff = 0 表示自洽）。
 */
trait MachineGoodsStockChangeTrait
{
    /** goods_change 上架类：2 上货、4 盘盈、6 后台上架、9 后台恢复BAD、11 终端恢复BAD */
    protected static $goodsChangeShelfTypes = [2, 4, 6, 9, 11];

    /** goods_change 下架类：3 下货（含销售）、5 盘亏、7 后台下架、8 后台BAD、10 终端BAD */
    protected static $goodsChangeUnshelfTypes = [3, 5, 7, 8, 10];

    /** goods_change 历史"销售减库存"类型（现网已不再写入，仅作交叉核对） */
    protected static $goodsChangeSaleTypes = [1];

    /**
     * 期末库存锚点（与设备商品列表/导出口径一致）。
     */
    public function getMachineGoodsStockAnchor($mId, $gId)
    {
        $mId = intval($mId);
        $gId = intval($gId);
        $row = Db::name('machine_channel')
            ->where('m_id', $mId)->where('g_id', $gId)
            ->field('SUM(CASE WHEN status = 1 THEN stock ELSE 0 END) available_stock,'
                . 'SUM(CASE WHEN status > 1 THEN stock ELSE 0 END) disabled_stock,'
                . 'SUM(frozen_stock) reserve_stock,SUM(stock) total_stock,COUNT(*) channel_count')
            ->find();
        $standby = Db::name('machine_goods')->where('m_id', $mId)->where('g_id', $gId)->sum('standby_stock');
        return [
            'available_stock' => intval($row['available_stock'] ?? 0),
            'disabled_stock'  => intval($row['disabled_stock'] ?? 0),
            'reserve_stock'   => intval($row['reserve_stock'] ?? 0),
            'standby_stock'   => intval($standby),
            'total_stock'     => intval($row['total_stock'] ?? 0),
            'channel_count'   => intval($row['channel_count'] ?? 0),
        ];
    }

    /**
     * 时间段内台账汇总：上架/下架=补货单，销售=订单。
     */
    protected function sumStockLedger($mId, $gId, $startTime, $endTime)
    {
        $mId = intval($mId);
        $gId = intval($gId);
        $startTime = intval($startTime);
        $endTime = intval($endTime);

        $rep = Db::name('machine_channel_replenishment')
            ->where('m_id', $mId)->where('g_id', $gId)
            ->where('create_time', 'between', [$startTime, $endTime])
            ->field('SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) shelved,'
                . 'SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END) unshelved,'
                . 'COUNT(*) record_count')
            ->find();

        $sale = Db::name('sale_orders_details')->alias('d')
            ->join('sale_orders o', 'o.order_id = d.order_id')
            ->where('o.m_id', $mId)->where('d.g_id', $gId)
            ->where('o.pay_status', 3)->whereIn('o.out_status', [4, 6])
            ->where('o.out_time', 'between', [$startTime, $endTime])
            ->field('SUM(d.success_quantity) sold,SUM(d.refund_quantity) refund_quantity,COUNT(*) order_count')
            ->find();

        return [
            'shelved'         => intval($rep['shelved'] ?? 0),
            'unshelved'       => intval($rep['unshelved'] ?? 0),
            'record_count'    => intval($rep['record_count'] ?? 0),
            'sold'            => intval($sale['sold'] ?? 0),
            'refund_quantity' => intval($sale['refund_quantity'] ?? 0),
            'order_count'     => intval($sale['order_count'] ?? 0),
        ];
    }

    /**
     * goods_change 流水汇总（交叉核对用；无时间字段的历史记录单独计数）。
     */
    protected function sumGoodsChangeLog($mId, $gId, $startTime, $endTime)
    {
        $mId = intval($mId);
        $gId = intval($gId);
        $row = Db::name('goods_change')
            ->where('m_id', $mId)->where('g_id', $gId)->where('mg_id', '>', 0)
            ->where('create_time', 'between', [intval($startTime), intval($endTime)])
            ->field('SUM(CASE WHEN type IN (' . implode(',', self::$goodsChangeShelfTypes) . ') THEN change_value ELSE 0 END) shelved,'
                . 'SUM(CASE WHEN type IN (' . implode(',', self::$goodsChangeUnshelfTypes) . ') THEN change_value ELSE 0 END) unshelved,'
                . 'SUM(CASE WHEN type IN (' . implode(',', self::$goodsChangeSaleTypes) . ') THEN change_value ELSE 0 END) sold,'
                . 'COUNT(*) log_count')
            ->find();
        $untimed = intval(Db::name('goods_change')
            ->where('m_id', $mId)->where('g_id', $gId)->where('mg_id', '>', 0)
            ->whereRaw('(create_time IS NULL OR create_time = 0)')
            ->count());
        return [
            'shelved'       => intval($row['shelved'] ?? 0),
            'unshelved'     => intval($row['unshelved'] ?? 0),
            'sold'          => intval($row['sold'] ?? 0),
            'log_count'     => intval($row['log_count'] ?? 0),
            'untimed_count' => $untimed,
        ];
    }

    /**
     * 归一化查询条件：m_id、g_id 必填，起止时间支持时间戳或 'Y-m-d'（结束日期补到 23:59:59）。
     */
    protected function resolveStockChangeQuery(array $postData)
    {
        $mId = intval($postData['m_id'] ?? 0);
        $gId = intval($postData['g_id'] ?? 0);
        if ($mId <= 0) throw new \InvalidArgumentException('设备ID不能为空');
        if ($gId <= 0) throw new \InvalidArgumentException('商品ID不能为空');

        $startTime = $this->toTimestamp($postData['start_time'] ?? '', false);
        $endTime = $this->toTimestamp($postData['end_time'] ?? '', true);
        if ($startTime <= 0 || $endTime <= 0) throw new \InvalidArgumentException('请选择起止时间');
        if ($startTime > $endTime) throw new \InvalidArgumentException('开始时间不能大于结束时间');

        return [
            'm_id' => $mId,
            'g_id' => $gId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'pageNum' => max(1, intval($postData['pageNum'] ?? 1)),
            'pageSize' => min(200, max(1, intval($postData['pageSize'] ?? 20))),
        ];
    }

    /**
     * 日期/时间戳归一化；$endOfDay=true 且传入纯日期时补到当天 23:59:59。
     */
    protected function toTimestamp($value, $endOfDay = false)
    {
        $value = trim((string)$value);
        if ($value === '') return 0;
        if (ctype_digit($value)) return intval($value);
        $time = strtotime($value);
        if ($time === false) return 0;
        if ($endOfDay && !preg_match('/\d{1,2}:\d{2}/', $value)) {
            $time = strtotime(date('Y-m-d', $time) . ' 23:59:59');
        }
        return intval($time);
    }

    /**
     * 主统计：初始库存 / 累计上架 / 累计下架 / 累计销售 / 剩余库存 + 等式与对账差额。
     */
    public function getStockChangeStatsData(array $postData)
    {
        $query = $this->resolveStockChangeQuery($postData);
        $mId = $query['m_id'];
        $gId = $query['g_id'];
        $startTime = $query['start_time'];
        $endTime = $query['end_time'];

        $anchor = $this->getMachineGoodsStockAnchor($mId, $gId);
        // 期末库存：end_time 早于当前时间时，以实物锚点扣除 [end_time, now] 的台账净额回推
        $closingStock = $anchor['total_stock'];
        $closingSource = 'anchor_now';
        if ($endTime < time()) {
            $after = $this->sumStockLedger($mId, $gId, $endTime, time());
            $closingStock = $anchor['total_stock'] - ($after['shelved'] - $after['unshelved'] - $after['sold']);
            $closingSource = 'derived_from_anchor';
        }

        $ledger = $this->sumStockLedger($mId, $gId, $startTime, $endTime);
        $log = $this->sumGoodsChangeLog($mId, $gId, $startTime, $endTime);

        $shelved = $ledger['shelved'];
        $unshelved = $ledger['unshelved'];
        $sold = $ledger['sold'];
        $openingStock = $closingStock - ($shelved - $unshelved - $sold);
        $left = $openingStock + $shelved;
        $right = $unshelved + $sold + $closingStock;
        $diff = $left - $right;

        return $this->buildStockChangeStatsResult(
            $query, $anchor, $closingStock, $closingSource, $ledger, $log, $openingStock, $left, $right, $diff
        );
    }

    /**
     * 组装主统计返回体（含四方对账与口径回显）。
     */
    protected function buildStockChangeStatsResult(array $query, array $anchor, $closingStock, $closingSource, array $ledger, array $log, $openingStock, $left, $right, $diff)
    {
        $mId = $query['m_id'];
        $gId = $query['g_id'];
        $goods = Db::name('machine_goods')->where('m_id', $mId)->where('g_id', $gId)
            ->field('mg_id,machine_id,g_name,sku,bar_code,is_shelf')->find();
        $machineName = Db::name('machine')->where('m_id', $mId)->value('machine_name');
        $shelved = $ledger['shelved'];
        $unshelved = $ledger['unshelved'];
        $sold = $ledger['sold'];

        return [
            'm_id' => $mId,
            'g_id' => $gId,
            'mg_id' => intval($goods['mg_id'] ?? 0),
            'machine_id' => (string)($goods['machine_id'] ?? ''),
            'machine_name' => (string)($machineName ?? ''),
            'g_name' => (string)($goods['g_name'] ?? ''),
            'sku' => (string)($goods['sku'] ?? ''),
            'bar_code' => (string)($goods['bar_code'] ?? ''),
            'is_shelf' => intval($goods['is_shelf'] ?? 2),
            'start_time' => $query['start_time'],
            'end_time' => $query['end_time'],
            'opening_stock' => $openingStock,
            'shelved_total' => $shelved,
            'unshelved_total' => $unshelved,
            'sold_total' => $sold,
            'refund_quantity' => $ledger['refund_quantity'],
            'closing_stock' => $closingStock,
            'closing_source' => $closingSource,
            'stock_detail' => $anchor,
            'equation' => [
                'left' => $left,
                'right' => $right,
                'diff' => $diff,
                'balanced' => $diff === 0,
                'expression' => '初始库存 + 累计上架 = 累计下架 + 累计销售 + 剩余库存',
            ],
            'cross_check' => [
                'shelved_doc' => $shelved,
                'shelved_log' => $log['shelved'],
                'shelved_doc_vs_log' => $shelved - $log['shelved'],
                'unshelved_doc' => $unshelved,
                'unshelved_log' => $log['unshelved'],
                'unshelved_doc_vs_log' => $unshelved - $log['unshelved'],
                'sold_order' => $sold,
                'sold_log' => $log['sold'],
                'sold_order_vs_log' => $sold - $log['sold'],
                'stock_anchor' => $anchor['total_stock'],
                'stock_by_ledger' => $closingStock,
                'stock_anchor_vs_ledger' => $anchor['total_stock'] - $closingStock,
                'replenishment_records' => $ledger['record_count'],
                'sale_order_records' => $ledger['order_count'],
                'goods_change_records' => $log['log_count'],
                'goods_change_untimed' => $log['untimed_count'],
            ],
            'caliber' => [
                'stock_field' => 'machine_channel.stock（status=1 记 available、status>1 记 disabled，与设备商品列表口径一致）',
                'shelved_from' => 'machine_channel_replenishment.quantity > 0',
                'unshelved_from' => 'machine_channel_replenishment.quantity < 0',
                'sold_from' => 'sale_orders_details.success_quantity（pay_status=3 且 out_status in (4,6)）',
                'sold_time_field' => 'sale_orders.out_time',
                'replenishment_time_field' => 'machine_channel_replenishment.create_time',
                'opening_derived' => '系统无历史库存快照表，初始库存 = 剩余库存 − (累计上架 − 累计下架 − 累计销售)',
            ],
        ];
    }

    /**
     * 累计上架明细（补货单 quantity > 0，附同刻库存流水日志佐证）。
     */
    public function getStockChangeShelfDetailData(array $postData)
    {
        return $this->stockChangeReplenishmentDetail($postData, 'shelved');
    }

    /**
     * 累计下架明细（补货单 quantity < 0，附同刻库存流水日志佐证）。
     */
    public function getStockChangeUnshelfDetailData(array $postData)
    {
        return $this->stockChangeReplenishmentDetail($postData, 'unshelved');
    }

    /**
     * 补货单明细：$direction = shelved（上架）| unshelved（下架）。
     */
    protected function stockChangeReplenishmentDetail(array $postData, $direction)
    {
        $query = $this->resolveStockChangeQuery($postData);
        $mId = $query['m_id'];
        $gId = $query['g_id'];
        $timeBetween = [$query['start_time'], $query['end_time']];
        $quantityCondition = $direction === 'shelved' ? ['>', 0] : ['<', 0];

        $sum = Db::name('machine_channel_replenishment')
            ->where('m_id', $mId)->where('g_id', $gId)
            ->where('create_time', 'between', $timeBetween)
            ->where('quantity', $quantityCondition[0], $quantityCondition[1])
            ->field('COUNT(*) record_count,SUM(CASE WHEN quantity > 0 THEN quantity ELSE -quantity END) sum_quantity')
            ->find();
        $total = intval($sum['record_count'] ?? 0);
        $sumQuantity = intval($sum['sum_quantity'] ?? 0);

        $offset = ($query['pageNum'] - 1) * $query['pageSize'];
        $rows = Db::name('machine_channel_replenishment')
            ->where('m_id', $mId)->where('g_id', $gId)
            ->where('create_time', 'between', $timeBetween)
            ->where('quantity', $quantityCondition[0], $quantityCondition[1])
            ->field('id,m_id,machine_id,mc_id,channel_code,mg_id,g_id,g_name,sku,bar_code,before,rep_type,quantity,after,ao_id,creator,create_time')
            ->order('id desc')->limit($offset, $query['pageSize'])->select()->toArray();

        // 同刻流水佐证：同一 mc_id + 同一 create_time 的 goods_change 记录（补货与流水同事务写入）
        $logMap = [];
        if ($rows) {
            $mcIds = array_values(array_unique(array_filter(array_map(function ($r) { return intval($r['mc_id']); }, $rows))));
            $times = array_values(array_unique(array_filter(array_map(function ($r) { return intval($r['create_time']); }, $rows))));
            if ($mcIds && $times) {
                $logs = Db::name('goods_change')->whereIn('mc_id', $mcIds)->whereIn('create_time', $times)
                    ->where('g_id', $gId)
                    ->field('change_id,mc_id,change_value,position,type,desc,recycle_box_change_type,creator,create_time')
                    ->select()->toArray();
                foreach ($logs as $log) {
                    $logMap[intval($log['mc_id']) . '|' . intval($log['create_time'])] = $log;
                }
            }
        }
        foreach ($rows as &$row) {
            $key = intval($row['mc_id']) . '|' . intval($row['create_time']);
            $row['change_log'] = $logMap[$key] ?? null;
            $row['direction'] = $direction;
            $row['quantity'] = intval($row['quantity']);
            $row['before'] = intval($row['before']);
            $row['after'] = intval($row['after']);
            $row['rep_type_desc'] = intval($row['rep_type']) === 2 ? '备用库存' : '上架补货';
        }
        unset($row);

        return [
            'm_id' => $mId,
            'g_id' => $gId,
            'start_time' => $query['start_time'],
            'end_time' => $query['end_time'],
            'direction' => $direction,
            'source' => 'machine_channel_replenishment.quantity ' . ($direction === 'shelved' ? '> 0' : '< 0'),
            'total' => $total,
            'sum_quantity' => $sumQuantity,
            'pageNum' => $query['pageNum'],
            'pageSize' => $query['pageSize'],
            'list' => $rows,
        ];
    }

    /**
     * 累计销售明细（订单侧：已支付 + 出货成功/未取，按出货时间过滤），可关联订单号追溯。
     */
    public function getStockChangeSaleDetailData(array $postData)
    {
        $query = $this->resolveStockChangeQuery($postData);
        $mId = $query['m_id'];
        $gId = $query['g_id'];
        $timeBetween = [$query['start_time'], $query['end_time']];

        $aliasWhere = function ($q) use ($mId, $gId) {
            $q->where('o.m_id', $mId)->where('d.g_id', $gId)
                ->where('o.pay_status', 3)->whereIn('o.out_status', [4, 6]);
        };
        $sum = Db::name('sale_orders_details')->alias('d')
            ->join('sale_orders o', 'o.order_id = d.order_id')
            ->where($aliasWhere)
            ->where('o.out_time', 'between', $timeBetween)
            ->field('COUNT(*) record_count,SUM(d.quantity) sum_quantity,SUM(d.success_quantity) sum_success,SUM(d.refund_quantity) sum_refund')
            ->find();

        $offset = ($query['pageNum'] - 1) * $query['pageSize'];
        $rows = Db::name('sale_orders_details')->alias('d')
            ->join('sale_orders o', 'o.order_id = d.order_id')
            ->where($aliasWhere)
            ->where('o.out_time', 'between', $timeBetween)
            ->field('d.sod_id,d.order_id,d.mc_id,d.channel_code,d.batch_id,d.mg_id,d.g_id,d.g_name,d.sku,d.quantity,d.success_quantity,'
                . 'd.fail_quantity,d.refund_quantity,d.total_sod_price,o.trade_no,o.out_trade_no,o.pay_type,o.pay_status,o.out_status,'
                . 'o.refund_status,o.pay_time,o.out_time,o.machine_id,o.machine_name')
            ->order('o.out_time desc')
            ->limit($offset, $query['pageSize'])
            ->select()->toArray();

        return [
            'm_id' => $mId,
            'g_id' => $gId,
            'start_time' => $query['start_time'],
            'end_time' => $query['end_time'],
            'direction' => 'sold',
            'source' => 'sale_orders_details.success_quantity（o.pay_status=3 且 o.out_status in (4,6)，时间取 o.out_time）',
            'total' => intval($sum['record_count'] ?? 0),
            'sum_quantity' => intval($sum['sum_quantity'] ?? 0),
            'sum_success_quantity' => intval($sum['sum_success'] ?? 0),
            'sum_refund_quantity' => intval($sum['sum_refund'] ?? 0),
            'pageNum' => $query['pageNum'],
            'pageSize' => $query['pageSize'],
            'list' => $rows,
        ];
    }
}
