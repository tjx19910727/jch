<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 10:00
 */

namespace app\AppFactory\TimeTask\Machine;


use app\AppFactory\Kernel\Service\Stock\MachineGoodsStockSnapshotService;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelStockTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class MachineChannelStockClient extends TimeTaskBase
{
    use MachineTrait,MachineChannelTrait,MachineChannelStockTrait;

    /**
     * 定时任务-生成设备商品日终库存快照（库存变化统计的"初始库存/剩余库存"数据来源）
     *
     * 用法：php think time_task machineChannelStock countMcStock       （每日 00:10，写入昨日日终快照）
     *       php think stock_snapshot daily --date=2026-09-16           （指定日期补 快照）
     *
     * @param string $statDate 快照代表日期（Y-m-d），留空=昨天
     * @return string
     */
    public function countMcStock($statDate = '')
    {
        $result = (new MachineGoodsStockSnapshotService())
            ->buildSnapshot($statDate, MachineGoodsStockSnapshotService::SOURCE_DAILY);
        return sprintf(
            '库存快照完成：stat_date=%s 写入=%d 行（覆盖同日期历史 %d 行）',
            $result['stat_date'],
            $result['inserted'],
            $result['deleted']
        );
    }

    /**
     * 定时任务-历史快照回填（以当前货道库存为锚点，按日台账 上架−下架−销售 逐日反推）
     *
     * 用法：php think stock_snapshot backfill --start=2026-06-19 --end=2026-09-16
     *
     * @param string $startDate 起始日期（含），留空=90 天前
     * @param string $endDate   结束日期（含），留空=昨天
     * @return string
     */
    public function backfillMcStock($startDate = '', $endDate = '')
    {
        $result = (new MachineGoodsStockSnapshotService())->backfill($startDate, $endDate);
        $verify = $result['verify_against_check_stock'] ?? [];
        return sprintf(
            '历史快照回填完成：%s ~ %s 共 %d 天，%d 个设备商品，写入 %d 行；盘点校验 %d 条',
            $result['start_date'],
            $result['end_date'],
            $result['days'],
            $result['anchor_count'],
            $result['inserted'],
            count($verify)
        );
    }
}