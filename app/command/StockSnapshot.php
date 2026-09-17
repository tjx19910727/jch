<?php
declare (strict_types = 1);

namespace app\command;

use app\AppFactory\Kernel\Service\Stock\MachineGoodsStockSnapshotService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 设备商品库存快照（方案 B）
 *
 * 用法：
 *   php think stock_snapshot daily                     生成昨日日终快照（每日 00:10 执行）
 *   php think stock_snapshot daily --date=2026-09-16   生成指定日期的日终快照
 *   php think stock_snapshot backfill                  回填最近 90 天（当前库存锚点 + 台账反推）
 *   php think stock_snapshot backfill --start=2026-06-19 --end=2026-09-16
 *
 * 说明：快照表为 machine_channel_stock（source 区分 daily/backfill），
 *      库存变化统计接口据此直接给出「初始库存 / 剩余库存」。
 */
class StockSnapshot extends Command
{
    protected function configure()
    {
        $this->setName('stock_snapshot')
            ->addArgument('action', Argument::REQUIRED, 'daily（日终快照）| backfill（历史回填）')
            ->addOption('date', null, Option::VALUE_OPTIONAL, '快照代表日期 Y-m-d（daily 用，留空=昨天）', '')
            ->addOption('start', null, Option::VALUE_OPTIONAL, '回填开始日期 Y-m-d（留空=90 天前）', '')
            ->addOption('end', null, Option::VALUE_OPTIONAL, '回填结束日期 Y-m-d（留空=昨天）', '')
            ->setDescription('设备商品库存快照：daily 生成日终快照 / backfill 回填历史');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = strval($input->getArgument('action'));
        $service = new MachineGoodsStockSnapshotService();
        try {
            if ($action === 'daily') {
                $date = strval($input->getOption('date'));
                $result = $service->buildSnapshot($date, MachineGoodsStockSnapshotService::SOURCE_DAILY);
                $output->writeln(sprintf(
                    '库存快照完成：stat_date=%s 写入=%d 行（覆盖同日期历史 %d 行）',
                    $result['stat_date'],
                    $result['inserted'],
                    $result['deleted']
                ));
                return 0;
            }

            if ($action === 'backfill') {
                $start = strval($input->getOption('start'));
                $end = strval($input->getOption('end'));
                $result = $service->backfill($start, $end);
                $output->writeln(sprintf(
                    '历史快照回填完成：%s ~ %s 共 %d 天，%d 个设备商品，写入 %d 行',
                    $result['start_date'],
                    $result['end_date'],
                    $result['days'],
                    $result['anchor_count'],
                    $result['inserted']
                ));
                foreach (($result['verify_against_check_stock'] ?? []) as $row) {
                    $output->writeln(sprintf(
                        '  盘点校验 m_id=%d %s 盘点系统库存=%d 快照合计=%d 差额=%d',
                        $row['m_id'],
                        $row['stat_date'],
                        $row['check_system_stock'],
                        $row['snapshot_total_stock'],
                        $row['diff']
                    ));
                }
                return 0;
            }

            $output->writeln('未知 action：' . $action . '（支持 daily / backfill）');
            return 1;
        } catch (\Throwable $e) {
            actionException($e, 1, 'stock_snapshot');
            $output->writeln('执行失败：' . $e->getMessage());
            return 1;
        }
    }
}
