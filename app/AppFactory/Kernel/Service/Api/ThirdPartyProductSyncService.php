<?php

namespace app\AppFactory\Kernel\Service\Api;

use app\AppFactory\Kernel\Model\Api\ApiCallbackModel;
use app\AppFactory\Kernel\Model\Api\ThirdPartySyncDirtyModel;
use think\facade\Db;

/**
 * 将聚合后的商品变化转换为 api_callback 主动推送任务。
 */
class ThirdPartyProductSyncService
{
    public const TYPE_MACHINE_INVENTORY = 'machine_inventory';
    public const TYPE_CORE_GOODS = 'core_goods';
    public const CALLBACK_MACHINE_INVENTORY = 12;
    public const CALLBACK_CORE_GOODS = 13;

    private $config;
    private $snapshotService;
    private $payloadBuilder;

    public function __construct()
    {
        $this->config = $this->resolveConfig();
        $this->snapshotService = new ThirdPartyProductSnapshotService();
        $this->payloadBuilder = new ThirdPartyProductSyncPayloadBuilder(
            $this->config['secret'] ?? ''
        );
    }

    /**
     * 解析配置（两级）：
     * 1) config/third_party_sync.php —— 随代码版本携带的默认配置；
     * 2) runtime/third_party_sync.local.php —— 服务器可写目录中的覆盖文件，
     *    当 config 目录在服务器上不可读/未更新时作为兜底，文件不存在则忽略。
     * 两级均不可用时返回空配置（enabled=false，安全禁用，绝不误发）。
     */
    private function resolveConfig(): array
    {
        $main = (array)config('third_party_sync');
        $local = $this->loadLocalOverride();
        $cfg = array_merge($main, $local);

        // 测试/生产接收服务器：与微程支付一致，以 env("CglPay.is_test") 判定。
        // 显式配置（config 顶层或 runtime local）优先，未配置时按分支选择。
        $branchUrls = (array)($cfg['urls'][env('CglPay.is_test') ? 'test' : 'prod'] ?? []);
        foreach (['core_goods_url', 'machine_inventory_url'] as $key) {
            if (empty($cfg[$key])) {
                $cfg[$key] = (string)($branchUrls[$key] ?? '');
            }
        }
        unset($cfg['urls']);

        return $cfg;
    }

    private function loadLocalOverride(): array
    {
        $file = root_path() . 'runtime/third_party_sync.local.php';
        if (!is_file($file)) {
            return [];
        }
        try {
            $override = @include $file;
            return is_array($override) ? $override : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 扫描尚未派发的版本并生成本地 api_callback，HTTP 请求由既有回调任务异步执行。
     */
    public function dispatch($limit = 0)
    {
        $error = $this->getConfigError();
        if ($error !== '') {
            return ['queued' => 0, 'failed' => 0, 'skipped' => 0, 'message' => $error];
        }

        $limit = intval($limit ?: ($this->config['batch_size'] ?? 100));
        $limit = max(1, min($limit, 1000));
        $ids = ThirdPartySyncDirtyModel::where([])
            ->whereColumn('version', '>', 'dispatched_version')
            ->order('changed_at asc,id asc')
            ->limit($limit)
            ->column('id');

        $result = ['queued' => 0, 'failed' => 0, 'skipped' => 0, 'message' => ''];
        foreach ($ids as $id) {
            try {
                $status = $this->dispatchOne(intval($id));
                $result[$status]++;
            } catch (\Throwable $e) {
                $result['failed']++;
                try {
                    actionException($e, 1, 'ThirdPartyProductSync');
                } catch (\Throwable $logException) {
                }
            }
        }
        return $result;
    }

    public function enqueueMachine($machineId)
    {
        $machineId = trim((string)$machineId);
        if ($machineId === '') {
            return false;
        }
        return $this->upsertDirty(self::TYPE_MACHINE_INVENTORY, $machineId, 'snapshot');
    }

    public function enqueueGoods($productId, $operation = 'upsert')
    {
        $productId = intval($productId);
        if ($productId <= 0) {
            return false;
        }
        $operation = $operation === 'delete' ? 'delete' : 'upsert';
        return $this->upsertDirty(self::TYPE_CORE_GOODS, (string)$productId, $operation);
    }

    public function enqueueAllMachines()
    {
        $machineIds = Db::name('machine')
            ->where('ao_id', ThirdPartyProductSnapshotService::CORE_AO_ID)
            ->where('machine_id', '<>', '')
            ->column('machine_id');
        $count = 0;
        foreach (array_unique($machineIds) as $machineId) {
            if ($this->enqueueMachine($machineId)) {
                $count++;
            }
        }
        return $count;
    }

    public function enqueueAllGoods()
    {
        $productIds = Db::name('goods')
            ->where('ao_id', ThirdPartyProductSnapshotService::CORE_AO_ID)
            ->column('g_id');
        $count = 0;
        foreach (array_unique($productIds) as $productId) {
            if ($this->enqueueGoods($productId)) {
                $count++;
            }
        }
        return $count;
    }

    private function dispatchOne($id)
    {
        // 行锁保证同一聚合对象不会并发生成重复版本；回调和派发水位在同一本地事务提交。
        return Db::transaction(function () use ($id) {
            $dirty = Db::name('third_party_sync_dirty')->where('id', $id)->lock(true)->find();
            if (!$dirty || intval($dirty['version']) <= intval($dirty['dispatched_version'])) {
                return 'skipped';
            }

            $syncType = (string)$dirty['sync_type'];
            $aggregateId = (string)$dirty['aggregate_id'];
            $version = intval($dirty['version']);
            $eventId = $this->newEventId();
            $timestamp = time();

            if ($syncType === self::TYPE_MACHINE_INVENTORY) {
                $payload = $this->payloadBuilder->buildMachineInventory(
                    $aggregateId,
                    $this->snapshotService->getMachineInventorySnapshot($aggregateId),
                    $version,
                    $eventId,
                    $timestamp
                );
                $callbackType = self::CALLBACK_MACHINE_INVENTORY;
                $notifyUrl = (string)($this->config['machine_inventory_url'] ?? '');
            } elseif ($syncType === self::TYPE_CORE_GOODS) {
                $goods = $this->snapshotService->getCoreGoodsSnapshot($aggregateId);
                $operation = $dirty['operation'] === 'delete' || !$goods ? 'delete' : 'upsert';
                $payload = $this->payloadBuilder->buildSyncGoods(
                    $aggregateId,
                    $goods,
                    $operation
                );
                $callbackType = self::CALLBACK_CORE_GOODS;
                $notifyUrl = (string)($this->config['core_goods_url'] ?? '');
            } else {
                Db::name('third_party_sync_dirty')->where('id', $id)->update([
                    'dispatched_version' => $version,
                    'update_time' => time(),
                ]);
                return 'skipped';
            }

            if ($notifyUrl === '') {
                // 该类型接收地址未配置（如 syncMachineProduct 报文待文档确认）。
                // 直接推进水位，避免残留记录长期占用扫描；
                // 配置完成后可用 think third_party_sync machine|goods all 全量补发。
                Db::name('third_party_sync_dirty')->where('id', $id)->update([
                    'dispatched_version' => $version,
                    'update_time' => time(),
                ]);
                return 'skipped';
            }
            $message = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($message === false) {
                throw new \RuntimeException('第三方商品同步请求 JSON 编码失败');
            }

            $callback = ApiCallbackModel::create([
                'aa_id' => 0,
                'uuid' => $eventId,
                'notify_url' => $notifyUrl,
                'callback_type' => $callbackType,
                'message' => $message,
            ]);

            Db::name('third_party_sync_dirty')
                ->where('id', $id)
                ->where('version', $version)
                ->update([
                    'dispatched_version' => $version,
                    'update_time' => time(),
                ]);

            try {
                actionLog([
                    'ac_id' => intval($callback->ac_id ?? 0),
                    'event_id' => $eventId,
                    'sync_type' => $syncType,
                    'aggregate_id' => $aggregateId,
                    'version' => $version,
                ], '生成第三方商品同步回调', 'ThirdPartyProductSync');
            } catch (\Throwable $logException) {
            }
            return 'queued';
        });
    }

    private function upsertDirty($syncType, $aggregateId, $operation)
    {
        // 只合并变化并递增版本，不在业务写入链路中访问第三方网络。
        $now = time();
        $sql = 'INSERT INTO `third_party_sync_dirty` '
            . '(`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`) '
            . 'VALUES (?,?,?,1,?,?,?) '
            . 'ON DUPLICATE KEY UPDATE `operation`=VALUES(`operation`),`version`=`version`+1,'
            . '`changed_at`=VALUES(`changed_at`),`update_time`=VALUES(`update_time`)';
        return Db::execute($sql, [$syncType, $aggregateId, $operation, $now, $now, $now]) > 0;
    }

    private function getConfigError()
    {
        if (empty($this->config['enabled'])) {
            return '第三方商品同步未启用';
        }
        if (empty($this->config['secret'])) {
            return '第三方商品同步签名密钥（apisecret）未配置';
        }
        return '';
    }

    private function newEventId()
    {
        try {
            $hex = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            $hex = md5(uniqid('', true) . mt_rand());
        }
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
}
