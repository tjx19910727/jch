<?php

namespace app\AppFactory\Kernel\Service\Api;

use think\facade\Db;

/**
 * 第三方商品同步——应用层增量扫描（无触发器方案 C 的兜底通道）。
 *
 * 触发器方案由数据库在 goods/machine_channel 写入时自动聚合 dirty；
 * 若某环境不便建触发器，或担心业务写点漏埋，可用本服务定时扫描业务表
 * update_time 变化，将 ao_id=17 的变化对象聚合进 third_party_sync_dirty。
 * 后续仍由 third_party_sync dispatch → api callback trigger_send 推送微程。
 *
 * 语义说明：
 *  - 以缓存中的单向游标（unix 秒）为窗口下界，扫描 [cursor, now) 内
 *    goods / machine_channel / machine 的 update_time 变化；
 *  - 游标不存在（首跑）或 --full 强制时执行"全量引导"：直接聚合全部
 *    ao_id=17 商品与设备，并重置游标为当前时间；
 *  - 每轮每类处理数量受 limit 限制，超限不推进游标，下一轮继续处理同一窗口；
 *  - MySQL GET_LOCK 保证同一时刻只有一个扫描进程，避免重复扫描；
 *  - 本服务只写 dirty（本地表、不访问第三方网络），enabled=false 时也可运行，
 *    便于先聚合、后开闸。
 *
 * 已知局限（需在部署文档中说明）：
 *  - 物理删除（goods DELETE / machine_channel DELETE）无法靠 update_time 捕获，
 *    下架通常用 status 变更（可捕获）；确需物理删除同步的写点建议走 A 层埋点；
 *  - 依赖业务写入时 update_time 被更新（Model 自动时间戳 / 显式 update_time）。
 */
class ThirdPartyProductScanService
{
    /** @var string 扫描游标缓存键（单向前进，勿手动回拨导致重复推送） */
    public const CURSOR_KEY = 'third_party_sync_scan_cursor';

    /** @var string MySQL 命名锁，防止并发扫描同一窗口 */
    public const LOCK_NAME = 'third_party_sync_scan_lock';

    /** @var int 核心主体 */
    public const CORE_AO_ID = 17;

    /** @var ThirdPartyProductSyncService */
    private $sync;

    public function __construct()
    {
        $this->sync = new ThirdPartyProductSyncService();
    }

    /**
     * 扫描并聚合变化对象。
     *
     * @param int  $limitPerType   每类单批处理上限；超过则不推进游标，下轮继续
     * @param bool $dryRun         只统计不写 dirty、不推进游标（联调/预演）
     * @param bool $fullInit       强制全量引导并重置游标
     * @param int  $cursorOverride 指定窗口起始 unix 秒（测试/恢复水位用；>0 优先）
     * @return array
     */
    public function scan($limitPerType = 1000, $dryRun = false, $fullInit = false, $cursorOverride = 0)
    {
        $limitPerType = max(1, intval($limitPerType));

        $locked = Db::query('SELECT GET_LOCK(?, 10) AS got', [self::LOCK_NAME]);
        if (empty($locked[0]['got'])) {
            return ['mode' => 'locked', 'message' => '已有其他扫描任务持有锁，本次跳过'];
        }

        try {
            $cursorStart = intval($cursorOverride);
            if ($fullInit || $cursorStart <= 0) {
                $cursorStart = intval(cache(self::CURSOR_KEY));
            }
            if ($fullInit || $cursorStart <= 0) {
                return $this->fullInit($dryRun);
            }
            return $this->scanIncremental($cursorStart, $limitPerType, $dryRun, $cursorOverride > 0);
        } finally {
            Db::query('SELECT RELEASE_LOCK(?)', [self::LOCK_NAME]);
        }
    }



    /**
     * 全量引导：聚合全部 ao_id=17 商品与设备，重置游标。
     */
    protected function fullInit($dryRun)
    {
        if ($dryRun) {
            $goods = intval(Db::name('goods')->where('ao_id', self::CORE_AO_ID)->count());
            $machines = intval(Db::name('machine')
                ->where('ao_id', self::CORE_AO_ID)
                ->where('machine_id', '<>', '')
                ->count());
        } else {
            $goods = $this->sync->enqueueAllGoods();
            $machines = $this->sync->enqueueAllMachines();
            cache(self::CURSOR_KEY, time());
        }

        return [
            'mode' => 'full_init',
            'goods' => intval($goods),
            'machines' => intval($machines),
            'message' => '全量引导：ao_id=' . self::CORE_AO_ID . ' 核心商品与设备已聚合入队，游标已重置',
        ];
    }

    /**
     * 增量扫描单个窗口 [cursorStart, now)。
     */
    protected function scanIncremental($cursorStart, $limitPerType, $dryRun, $keepCursor)
    {
        $windowEnd = time();
        $stat = [
            'mode' => 'incremental',
            'window_start' => intval($cursorStart),
            'window_end' => intval($windowEnd),
            'goods' => 0,                      // 变化的核心商品数
            'machines_from_goods' => 0,        // 由商品变化牵出的设备数（含该商品的 ao_id=17 机器）
            'machines_from_channel' => 0,      // machine_channel 变化直接命中设备数
            'machines_from_machine' => 0,      // machine 行变化（归属/资料）命中设备数
            'truncated' => false,              // true 表示单轮超限未扫完，游标不推进
        ];

        // ---- 1) 核心商品变化：goods(ao_id=17).update_time ----
        $goodsRows = Db::query(
            'SELECT g_id FROM goods WHERE ao_id = ? AND update_time >= ? AND update_time < ? AND update_time > 0 ORDER BY g_id LIMIT ' . ($limitPerType + 1),
            [self::CORE_AO_ID, $cursorStart, $windowEnd]
        );
        $goodsTruncated = count($goodsRows) > $limitPerType;
        $goodsIds = array_values(array_unique(array_map('intval', array_column(
            array_slice($goodsRows, 0, $limitPerType), 'g_id'
        ))));

        // ---- 2) 上述商品变化牵出的核心设备（整机快照含商品资料，需联动） ----
        $machineIdsFromGoods = [];
        if ($goodsIds) {
            $in = implode(',', $goodsIds);
            $rows = Db::query(
                "SELECT DISTINCT mc.machine_id FROM machine_channel mc "
                . 'INNER JOIN machine m ON m.machine_id = mc.machine_id AND m.ao_id = ? '
                . "WHERE mc.g_id IN ($in) AND mc.machine_id <> ''",
                [self::CORE_AO_ID]
            );
            $machineIdsFromGoods = array_values(array_unique(array_filter(array_map('strval', array_column($rows, 'machine_id')))));
        }


        // ---- 3) 货道（设备商品载体）变化：machine_channel.update_time ----
        $channelRows = Db::query(
            "SELECT DISTINCT mc.machine_id FROM machine_channel mc "
            . 'INNER JOIN machine m ON m.machine_id = mc.machine_id AND m.ao_id = ? '
            . "WHERE mc.update_time >= ? AND mc.update_time < ? AND mc.update_time > 0 AND mc.machine_id <> '' "
            . 'ORDER BY mc.machine_id LIMIT ' . ($limitPerType + 1),
            [self::CORE_AO_ID, $cursorStart, $windowEnd]
        );
        $channelTruncated = count($channelRows) > $limitPerType;
        $machineIdsFromChannel = array_values(array_unique(array_filter(array_map('strval', array_column(
            array_slice($channelRows, 0, $limitPerType), 'machine_id'
        )))));

        // ---- 4) 设备行变化：machine(ao_id=17).update_time（覆盖新纳入核心主体的机器） ----
        $machineRows = Db::query(
            'SELECT machine_id FROM machine WHERE ao_id = ? AND update_time >= ? AND update_time < ? AND update_time > 0 AND machine_id <> \'\' ORDER BY machine_id LIMIT ' . ($limitPerType + 1),
            [self::CORE_AO_ID, $cursorStart, $windowEnd]
        );
        $machineTruncated = count($machineRows) > $limitPerType;
        $machineIdsFromMachine = array_values(array_unique(array_filter(array_map('strval', array_column(
            array_slice($machineRows, 0, $limitPerType), 'machine_id'
        )))));

        $stat['goods'] = count($goodsIds);
        $stat['machines_from_goods'] = count($machineIdsFromGoods);
        $stat['machines_from_channel'] = count($machineIdsFromChannel);
        $stat['machines_from_machine'] = count($machineIdsFromMachine);
        $stat['truncated'] = $goodsTruncated || $channelTruncated || $machineTruncated;

        if (!$dryRun) {
            foreach ($goodsIds as $gId) {
                $this->sync->enqueueGoods($gId);
            }
            $writeMachines = array_values(array_unique(array_merge(
                $machineIdsFromGoods,
                $machineIdsFromChannel,
                $machineIdsFromMachine
            )));
            foreach ($writeMachines as $machineId) {
                $this->sync->enqueueMachine($machineId);
            }
            $stat['machines'] = count($writeMachines);

            // 全部处理完才推进游标；超限或指定了 override 时保持原水位，下轮继续。
            if (!$stat['truncated'] && !$keepCursor) {
                cache(self::CURSOR_KEY, $windowEnd);
            }
        }

        return $stat;
    }
}
