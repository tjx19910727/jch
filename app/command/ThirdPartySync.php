<?php
declare(strict_types=1);

namespace app\command;

use app\AppFactory\Kernel\Service\Api\ThirdPartyProductScanService;
use app\AppFactory\Kernel\Service\Api\ThirdPartyProductSyncService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 扫描聚合变更，或为首次全量同步、人工补偿生成待处理记录。
 */
class ThirdPartySync extends Command
{
    protected function configure()
    {
        $this->setName('third_party_sync')
            ->addArgument('action', Argument::OPTIONAL, 'dispatch|machine|goods|scan', 'dispatch')
            ->addArgument('target', Argument::OPTIONAL, '设备编号、商品ID或 all', '')
            ->addOption('limit', null, Option::VALUE_OPTIONAL, '单批处理数量（0 表示使用默认值）', '0')
            ->addOption('daemon', null, Option::VALUE_NONE, '常驻扫描待同步数据')
            ->addOption('sleep', null, Option::VALUE_OPTIONAL, '常驻扫描间隔秒数', '5')
            ->addOption('dry-run', null, Option::VALUE_NONE, 'scan 仅统计不写 dirty、不推进游标')
            ->addOption('full', null, Option::VALUE_NONE, 'scan 强制全量引导并重置游标')
            ->addOption('cursor', null, Option::VALUE_OPTIONAL, 'scan 指定窗口起始 unix 秒（测试/恢复水位）', '0')
            ->setDescription('第三方设备商品及核心商品主动同步');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new ThirdPartyProductSyncService();
        $action = (string)$input->getArgument('action');
        $target = (string)$input->getArgument('target');

        if ($action === 'machine') {
            $count = strtolower($target) === 'all'
                ? $service->enqueueAllMachines()
                : ($service->enqueueMachine($target) ? 1 : 0);
            $output->writeln('设备商品待同步记录生成数量：' . $count);
            return 0;
        }
        if ($action === 'goods') {
            $count = strtolower($target) === 'all'
                ? $service->enqueueAllGoods()
                : ($service->enqueueGoods($target) ? 1 : 0);
            $output->writeln('核心商品待同步记录生成数量：' . $count);
            return 0;
        }
        if ($action === 'scan') {
            // 应用层增量扫描（无触发器兜底）：扫 goods/machine_channel/machine 的
            // update_time 变化并入 dirty；只写本地表，不发 HTTP。
            $scanService = new ThirdPartyProductScanService();
            $scanLimit = intval($input->getOption('limit'));
            $scanLimit = $scanLimit > 0 ? $scanLimit : 1000;
            $result = $scanService->scan(
                $scanLimit,
                (bool)$input->getOption('dry-run'),
                (bool)$input->getOption('full'),
                intval($input->getOption('cursor'))
            );
            $output->writeln(json_encode($result, JSON_UNESCAPED_UNICODE));
            return 0;
        }
        if ($action !== 'dispatch') {
            $output->writeln('未支持的 action：' . $action);
            return 1;
        }

        // dispatch 只生成 api_callback；实际 HTTP 发送仍由 api callback trigger_send 负责。
        $limit = intval($input->getOption('limit'));
        if (!$input->getOption('daemon')) {
            $output->writeln(json_encode($service->dispatch($limit), JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $sleep = max(1, intval($input->getOption('sleep')));
        $output->writeln('第三方商品同步扫描进程启动，间隔 ' . $sleep . ' 秒');
        while (true) {
            try {
                $result = $service->dispatch($limit);
                if ($result['queued'] || $result['failed']) {
                    $output->writeln(date('Y-m-d H:i:s') . ' ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                }
            } catch (\Throwable $e) {
                try {
                    actionException($e, 1, 'ThirdPartyProductSyncCommand');
                } catch (\Throwable $logException) {
                }
                $output->writeln(date('Y-m-d H:i:s') . ' 扫描异常：' . $e->getMessage());
            }
            sleep($sleep);
        }
    }
}
