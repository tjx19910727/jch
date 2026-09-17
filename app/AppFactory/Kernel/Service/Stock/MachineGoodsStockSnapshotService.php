<?php

namespace app\AppFactory\Kernel\Service\Stock;

use think\facade\Db;

/**
 * 设备商品库存快照服务（方案 B）
 *
 * 目的：把"某日期的库存"落成物理事实，使库存变化统计的「初始库存/剩余库存」可直接查库，
 *      等式 初始库存 + 累计上架 = 累计下架 + 累计销售 + 剩余库存 成为**可校验的真实对账**。
 *
 * 快照表复用既有 machine_channel_stock（原 countMcStock 定时任务因"改实时查询"停用，表为空）：
 *   create_date   该快照代表的日期（该日 0 点时间戳，语义 = 该日**日终**库存）
 *   mc_stock      status=1 货道库存合计（available）
 *   bad_stock     status=3 货道库存合计
 *   pre_stock     frozen_stock 合计（预定）
 *   standby_stock 备用库存
 *   total_stock   全部货道库存合计（status 不限）
 *   source        daily=每日定时任务（拆分准确）/ backfill=历史回填（仅 total 近似，无法逐日拆分 status）
 *
 * 口径与 MachineGoodsModel::STOCK_FIELDS、machine_channel_stock_report 视图保持一致。
 */
class MachineGoodsStockSnapshotService
{
    const SOURCE_DAILY = 'daily';
    const SOURCE_BACKFILL = 'backfill';

    /** 单批插入行数，避免大事务与超长 SQL */
    protected $chunkSize = 2000;

    /**
     * 生成指定日期的库存快照（幂等：同日期先删后插）。
     *
     * @param string $statDate 快照代表日期（Y-m-d）；留空=昨天（每日 00:10 执行时代表昨日日终）
     * @param string $source   daily|backfill
     * @return array
     */
    public function buildSnapshot($statDate = '', $source = self::SOURCE_DAILY)
    {
        $statDate = $this->normalizeStatDate($statDate);
        $createDate = strtotime($statDate . ' 00:00:00');
        $rows = $this->aggregateCurrentStock();
        $deleted = Db::name('machine_channel_stock')->where('create_date', $createDate)->delete();

        $inserted = 0;
        $buffer = [];
        $now = time();
        foreach ($rows as $row) {
            $row['create_date'] = $createDate;
            $row['create_time'] = $now;
            $row['source'] = $source;
            $buffer[] = $row;
            $inserted++;
            if (count($buffer) >= $this->chunkSize) {
                Db::name('machine_channel_stock')->insertAll($buffer);
                $buffer = [];
            }
        }
        if (!empty($buffer)) {
            Db::name('machine_channel_stock')->insertAll($buffer);
        }

        return [
            'stat_date' => $statDate,
            'create_date' => $createDate,
            'source' => $source,
            'deleted' => intval($deleted),
            'inserted' => $inserted,
        ];
    }

    /**
     * 取某时刻（含当日）之前最近一条快照，用于「初始库存 / 剩余库存」。
     *
     * @param int $mId
     * @param int $gId
     * @param int $time 只取 create_date <= 该日期 0 点的快照
     * @param bool $strictBeforeDay true=严格早于该日（期初场景：日终快照才有意义）
     * @return array|null
     */
    public function findSnapshotAt($mId, $gId, $time, $strictBeforeDay = true)
    {
        $dayTs = strtotime(date('Y-m-d', intval($time)) . ' 00:00:00');
        $query = Db::name('machine_channel_stock')
            ->where('m_id', intval($mId))->where('g_id', intval($gId));
        if ($strictBeforeDay) {
            $query->where('create_date', '<', $dayTs);
        } else {
            $query->where('create_date', '<=', $dayTs);
        }
        $row = $query->order('create_date desc')->find();
        if (!$row) return null;
        $rawTotal = intval($row['total_stock']);
        return [
            'stat_date' => date('Y-m-d', intval($row['create_date'])),
            'available_stock' => max(0, intval($row['mc_stock'])),
            'disabled_stock' => max(0, $rawTotal - intval($row['mc_stock'])),
            'reserve_stock' => intval($row['pre_stock']),
            'standby_stock' => intval($row['standby_stock']),
            // 库存不可能为负：回填反推若得出负数（台账缺口信号）则钳制为 0，并保留原始值便于排查
            'total_stock' => max(0, $rawTotal),
            'raw_total_stock' => $rawTotal,
            'clamped' => $rawTotal < 0,
            'source' => (string)($row['source'] ?? ''),
        ];
    }

    /**
     * 回填历史快照：以"当前货道库存"为锚点，按日台账（上架−下架−销售）逐日反推。
     *
     * 反推只能得到总库存、无法逐日还原 status 拆分，故回填行 mc_stock = total_stock（近似，
     * source=backfill）；每日定时任务生成的行拆分准确（source=daily）。
     *
     * @param string $startDate 起始日期（含），留空=90 天前
     * @param string $endDate   结束日期（含），留空=昨天
     * @param string $source
     * @return array
     */
    public function backfill($startDate = '', $endDate = '', $source = self::SOURCE_BACKFILL)
    {
        $startDate = $startDate !== '' ? $this->normalizeStatDate($startDate) : date('Y-m-d', strtotime('-90 days'));
        $endDate = $endDate !== '' ? $this->normalizeStatDate($endDate) : date('Y-m-d', strtotime('-1 day'));
        if (strtotime($startDate) > strtotime($endDate)) {
            throw new \InvalidArgumentException('回填开始日期不能大于结束日期');
        }

        $anchors = [];
        foreach ($this->aggregateCurrentStock() as $row) {
            $anchors[intval($row['m_id']) . '_' . intval($row['g_id'])] = $row;
        }
        if (!$anchors) {
            return ['start_date' => $startDate, 'end_date' => $endDate, 'days' => 0, 'inserted' => 0, 'anchor_count' => 0];
        }

        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime = strtotime($endDate . ' 23:59:59');
        $netsByDay = $this->aggregateDailyNet($startTime, time());

        // 有盘点的日期强制写全量（盘点 system_stock 是权威锚点，便于人工对账）
        $checkDays = [];
        foreach (Db::query(
            "SELECT DISTINCT create_date FROM machine_check_stock_count WHERE type = 1 AND create_date BETWEEN ? AND ?",
            [strtotime($startDate . ' 00:00:00'), strtotime($endDate . ' 00:00:00')]
        ) as $row) {
            $checkDays[intval($row['create_date'])] = true;
        }

        // 逐日反推：stock(D) = 当前锚点 − Σ_{t > D 日末} net（keyset 累计，只遍历一次净额）
        $days = [];
        for ($t = strtotime($endDate . ' 00:00:00'); $t >= $startTime; $t -= 86400) {
            $days[] = date('Y-m-d', $t);
        }
        $running = [];
        $endDayStart = strtotime($endDate . ' 00:00:00');
        foreach ($netsByDay as $dayStart => $items) {
            if ($dayStart > $endDayStart) {
                foreach ($items as $key => $net) {
                    $running[$key] = ($running[$key] ?? 0) + $net;
                }
            }
        }
        $dayStock = [];
        foreach ($days as $day) {
            $dayStart = strtotime($day . ' 00:00:00');
            foreach ($anchors as $key => $anchor) {
                $dayStock[$day][$key] = intval($anchor['total_stock']) - intval($running[$key] ?? 0);
            }
            foreach ($netsByDay[$dayStart] ?? [] as $key => $net) {
                $running[$key] = ($running[$key] ?? 0) + $net;
            }
        }

        $inserted = 0;
        $now = time();
        $isBaselineDay = true;
        foreach ($days as $day) {
            $createDate = strtotime($day . ' 00:00:00');
            $dayStart = $createDate;
            Db::name('machine_channel_stock')->where('create_date', $createDate)->where('source', $source)->delete();
            $buffer = [];
            foreach ($anchors as $key => $anchor) {
                // 窗口首日与盘点日写全量；其余日期只写当日有净额变化的商品（读取时取"目标日或之前最近一条"）。
                $isFullDay = $isBaselineDay || isset($checkDays[$createDate]);
                if (!$isFullDay && intval($netsByDay[$dayStart][$key] ?? 0) === 0) {
                    continue;
                }
                $total = $dayStock[$day][$key];
                $buffer[] = [
                    'm_id' => intval($anchor['m_id']),
                    'machine_id' => (string)$anchor['machine_id'],
                    'machine_name' => (string)$anchor['machine_name'],
                    'g_id' => intval($anchor['g_id']),
                    'g_name' => (string)$anchor['g_name'],
                    'sku' => (string)$anchor['sku'],
                    'bar_code' => (string)$anchor['bar_code'],
                    'model' => (string)$anchor['model'],
                    'mc_stock' => $total,
                    'pre_stock' => 0,
                    'standby_stock' => intval($anchor['standby_stock']),
                    'bad_stock' => 0,
                    'total_stock' => $total,
                    'retail_price' => $anchor['retail_price'] ?? 0,
                    'ao_id' => intval($anchor['ao_id']),
                    'create_date' => $createDate,
                    'create_time' => $now,
                    'source' => $source,
                ];
                $inserted++;
                if (count($buffer) >= $this->chunkSize) {
                    Db::name('machine_channel_stock')->insertAll($buffer);
                    $buffer = [];
                }
            }
            if (!empty($buffer)) {
                Db::name('machine_channel_stock')->insertAll($buffer);
            }
            $isBaselineDay = false;
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => count($days),
            'anchor_count' => count($anchors),
            'inserted' => $inserted,
            'verify_against_check_stock' => $this->verifyAgainstCheckStock($startTime, $endTime),
        ];
    }

    /**
     * 当前库存锚点：直接取系统既有库存报表视图（口径与设备商品列表/库存报表完全一致）。
     *
     * @return array
     */
    protected function aggregateCurrentStock()
    {
        $rows = Db::name('machine_channel_stock_report')
            ->field('m_id,machine_id,machine_name,g_id,g_name,sku,bar_code,model,mc_stock,pre_stock,standby_stock,bad_stock,total_stock,retail_price,ao_id')
            ->where('g_id', '>', 0)
            ->select()->toArray();
        $result = [];
        foreach ($rows as $row) {
            foreach (['m_id', 'g_id', 'mc_stock', 'pre_stock', 'standby_stock', 'bad_stock', 'total_stock', 'ao_id'] as $field) {
                $row[$field] = intval($row[$field] ?? 0);
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 逐日台账净额：net = 上架 − 下架 − 销售（与库存变化统计接口口径一致）。
     *
     * @return array [日 0 点时间戳 => ['m_id_g_id' => net]]
     */
    protected function aggregateDailyNet($startTime, $endTime)
    {
        $result = [];
        $rep = Db::query(
            "SELECT mc.m_id, mc.g_id, UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(mc.create_time))) day_start,
                    SUM(CASE WHEN mc.quantity > 0 THEN mc.quantity ELSE 0 END) up_qty,
                    SUM(CASE WHEN mc.quantity < 0 THEN -mc.quantity ELSE 0 END) down_qty
             FROM machine_channel_replenishment mc
             WHERE mc.g_id > 0 AND mc.create_time BETWEEN ? AND ?
             GROUP BY mc.m_id, mc.g_id, day_start",
            [$startTime, $endTime]
        );
        foreach ($rep as $row) {
            $key = intval($row['m_id']) . '_' . intval($row['g_id']);
            $day = intval($row['day_start']);
            $result[$day][$key] = ($result[$day][$key] ?? 0) + intval($row['up_qty']) - intval($row['down_qty']);
        }

        $sale = Db::query(
            "SELECT o.m_id, d.g_id, UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(o.out_time))) day_start,
                    SUM(d.success_quantity) sold_qty
             FROM sale_orders_details d
             JOIN sale_orders o ON o.order_id = d.order_id
             WHERE d.g_id > 0 AND o.pay_status = 3 AND o.out_status IN (4, 6) AND o.out_time BETWEEN ? AND ?
             GROUP BY o.m_id, d.g_id, day_start",
            [$startTime, $endTime]
        );
        foreach ($sale as $row) {
            $key = intval($row['m_id']) . '_' . intval($row['g_id']);
            $day = intval($row['day_start']);
            $result[$day][$key] = ($result[$day][$key] ?? 0) - intval($row['sold_qty']);
        }

        return $result;
    }

    /**
     * 用"设备级货架盘点"的系统库存校验快照（盘点 system_stock 与快照 total_stock 合计对比）。
     */
    protected function verifyAgainstCheckStock($startTime, $endTime)
    {
        $anchors = Db::query(
            "SELECT c.m_id, c.machine_id, c.create_date, c.system_stock, c.check_stock, c.create_time
             FROM machine_check_stock_count c
             JOIN (SELECT m_id, create_date, MAX(create_time) mt FROM machine_check_stock_count
                   WHERE type = 1 AND create_date BETWEEN ? AND ? GROUP BY m_id, create_date) t
               ON t.m_id = c.m_id AND t.create_date = c.create_date AND t.mt = c.create_time
             WHERE c.type = 1 ORDER BY c.create_date DESC LIMIT 20",
            [strtotime(date('Y-m-d', $startTime) . ' 00:00:00'), strtotime(date('Y-m-d', $endTime) . ' 00:00:00')]
        );
        $verify = [];
        foreach ($anchors as $anchor) {
            $snapshotStock = intval(Db::name('machine_channel_stock')
                ->where('m_id', intval($anchor['m_id']))
                ->where('create_date', intval($anchor['create_date']))
                ->sum('total_stock'));
            $verify[] = [
                'm_id' => intval($anchor['m_id']),
                'machine_id' => (string)$anchor['machine_id'],
                'stat_date' => date('Y-m-d', intval($anchor['create_date'])),
                'check_system_stock' => intval($anchor['system_stock']),
                'snapshot_total_stock' => $snapshotStock,
                'diff' => intval($anchor['system_stock']) - $snapshotStock,
            ];
        }
        return $verify;
    }

    /**
     * 日期归一化（支持 Y-m-d / Y/m/d / 时间戳）；留空=昨天。
     */
    protected function normalizeStatDate($date)
    {
        $date = trim((string)$date);
        if ($date === '') {
            return date('Y-m-d', strtotime('-1 day'));
        }
        if (ctype_digit($date)) {
            return date('Y-m-d', intval($date));
        }
        $time = strtotime($date);
        if ($time === false) {
            throw new \InvalidArgumentException('日期格式不正确：' . $date);
        }
        return date('Y-m-d', $time);
    }
}
