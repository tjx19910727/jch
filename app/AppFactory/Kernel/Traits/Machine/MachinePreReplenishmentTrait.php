<?php

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentDetailModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentLogModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentOrderModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentVideoModel;
use think\facade\Db;

/**
 * @property array $message
 */
trait MachinePreReplenishmentTrait
{
    use MachinePreReplenishmentGoodsSnapshotTrait;
    use MachinePreReplenishmentGoodsChangeTrait;

    protected function normalizeDetails($details)
    {
        $details = json2arr($details);
        if (!is_array($details)) {
            return [];
        }
        $details = array_values(array_filter($details, function ($item) {
            return is_array($item);
        }));

        $result = [];
        foreach ($details as $item) {
            $batchArr = json2arr($item['batch_arr'] ?? []);
            unset($item['batch_arr']);
            $batchArr = is_array($batchArr) ? array_values(array_filter($batchArr, function ($batch) {
                return is_array($batch);
            })) : [];

            if (!$batchArr) {
                $item['is_head'] = 1;
                $item['batch_id'] = (int)($item['batch_id'] ?? 0);
                $item['batch_sequence'] = (int)($item['batch_sequence'] ?? ($item['sequence'] ?? 1));
                $result[] = $item;
                continue;
            }

            $hasHead = false;
            foreach ($batchArr as $batch) {
                $isHead = isset($batch['is_head'])
                    ? (int)$batch['is_head'] === 1
                    : (isset($batch['status']) && (int)$batch['status'] === 1);
                if ($isHead) {
                    $hasHead = true;
                    break;
                }
            }
            if (!$hasHead) {
                array_unshift($batchArr, array_merge($item, ['is_head' => 1]));
            }

            foreach ($batchArr as $index => $batch) {
                $batchIsHead = isset($batch['is_head'])
                    ? (int)$batch['is_head'] === 1
                    : (isset($batch['status']) && (int)$batch['status'] === 1);
                $defaults = $batchIsHead ? $item : [
                    'machine_id' => $item['machine_id'] ?? '',
                    'mc_id' => $item['mc_id'] ?? 0,
                ];
                $normalized = array_merge($defaults, $batch);
                $isHead = isset($normalized['is_head'])
                    ? (int)$normalized['is_head'] === 1
                    : (isset($normalized['status']) && (int)$normalized['status'] === 1);
                $normalized['is_head'] = $isHead ? 1 : 2;
                $normalized['batch_id'] = (int)($normalized['batch_id'] ?? 0);
                $normalized['batch_sequence'] = (int)($normalized['batch_sequence']
                    ?? ($normalized['sequence'] ?? ($index + 1)));
                $normalized['plan_quantity'] = $normalized['plan_quantity'] ?? 0;
                $result[] = $normalized;
            }
        }
        return $result;
    }

    protected function buildOrderDetails($details, $originalDetailMap = [])
    {
        $machineIds = [];
        $mcIds = [];
        $goodsIds = [];
        $detailGroups = [];
        $groupPlanQuantityMap = [];
        foreach ($details as $item) {
            if (!isset($item['machine_id']) || !isset($item['mc_id'])) {
                return ['state' => 0, 'msg' => '明细参数不完整'];
            }

            $planQuantity = (int)($item['plan_quantity'] ?? 0);
            if ($planQuantity < 0) {
                return ['state' => 0, 'msg' => '预补数量不能小于0'];
            }

            $machineId = $item['machine_id'];
            $mcId = (int)$item['mc_id'];
            $groupKey = $machineId . '_' . $mcId;
            $machineIds[$machineId] = $machineId;
            $mcIds[$mcId] = $mcId;
            $goodsIds[] = $item['after_g_id'] ?? 0;
            $goodsIds[] = $item['g_id'] ?? 0;
            $detailGroups[$groupKey][] = $item;
            $groupPlanQuantityMap[$groupKey] = ($groupPlanQuantityMap[$groupKey] ?? 0) + $planQuantity;
        }

        $machines = MachineModel::where([['machine_id', 'in', array_values($machineIds)]])
            ->field('m_id,machine_id,machine_name')
            ->select()
            ->toArray();
        $machineMap = array_column($machines, null, 'machine_id');

        $mIds = array_column($machines, 'm_id');
        $machineMultiGoodsMap = [];
        if ($mIds) {
            $machineConfigRows = Db::name('machine_config')
                ->whereIn('m_id', $mIds)
                ->field('m_id,is_multi_goods')
                ->select()
                ->toArray();
            foreach ($machineConfigRows as $configRow) {
                $machineMultiGoodsMap[(int)$configRow['m_id']] = (int)($configRow['is_multi_goods'] ?? 2) === 1;
            }
        }

        $channels = [];
        if ($mIds) {
            $channels = MachineChannelModel::where([['m_id', 'in', $mIds]])
                ->field('m_id,mc_id,channel_code,channel_position,sku,g_name,pic,bar_code,g_id,stock,frozen_stock,out_fail_stock,capacity,is_multi_goods')
                ->select()
                ->toArray();
        }

        $channelMap = [];
        foreach ($channels as $channel) {
            $channelMap[$channel['m_id'] . '_' . $channel['mc_id']] = $channel;
            $goodsIds[] = $channel['g_id'] ?? 0;
        }

        $queueResult = $this->getActiveChannelGoodsBatchMap(array_values($mcIds));
        foreach ($queueResult['queues'] as $queue) {
            foreach ($queue as $batch) {
                $goodsIds[] = $batch['g_id'] ?? 0;
            }
        }

        foreach ($originalDetailMap as $originalDetail) {
            $goodsIds[] = $originalDetail['before_g_id'] ?? 0;
            $goodsIds[] = $originalDetail['g_id'] ?? 0;
        }
        $goodsMap = $this->getPreReplenishmentGoodsMap($goodsIds);

        $buildDetails = [];
        foreach ($detailGroups as $groupKey => $groupItems) {
            $firstItem = $groupItems[0];
            $machineId = $firstItem['machine_id'];
            $groupPlanQuantity = $groupPlanQuantityMap[$groupKey] ?? 0;
            if ($groupPlanQuantity <= 0) {
                return ['state' => 0, 'msg' => '每个货道至少需要一条预补数量大于0的明细'];
            }
            if (!isset($machineMap[$machineId])) {
                return ['state' => 0, 'msg' => '设备不存在:' . $machineId];
            }

            $machine = $machineMap[$machineId];
            $channelKey = $machine['m_id'] . '_' . (int)$firstItem['mc_id'];
            if (!isset($channelMap[$channelKey])) {
                return ['state' => 0, 'msg' => '货道不存在:' . $machineId . '-' . $firstItem['mc_id']];
            }

            $channel = $channelMap[$channelKey];
            if ((int)($channel['channel_position'] ?? 0) !== 1) {
                return [
                    'state' => 0,
                    'msg' => '预补货仅支持主柜货道:' . $machineId . '-' . $channel['channel_code'],
                ];
            }
            $isMultiGoods = !empty($machineMultiGoodsMap[(int)$machine['m_id']])
                && (int)($channel['is_multi_goods'] ?? 2) === 1;

            if (!$isMultiGoods) {
                if (count($groupItems) !== 1) {
                    return ['state' => 0, 'msg' => '普通货道只能保存一条预补货明细'];
                }

                $item = $groupItems[0];
                $original = $originalDetailMap['channel:' . $machineId . '_' . $channel['mc_id']] ?? [];
                $beforeGId = $original && array_key_exists('before_g_id', $original)
                    ? (int)$original['before_g_id']
                    : (int)($channel['g_id'] ?? 0);
                if ($original && (int)($channel['g_id'] ?? 0) !== $beforeGId) {
                    return ['state' => 0, 'msg' => '货道商品已发生变化，请刷新后重新编辑:' . $machineId . '-' . $channel['mc_id']];
                }

                $targetGId = $this->resolvePreReplenishmentTargetGId($item, $beforeGId);
                if ($targetGId <= 0) {
                    return [
                        'state' => 0,
                        'msg' => '空货道预补货必须选择商品:' . $machineId . '-' . $channel['channel_code'],
                    ];
                }
                $targetGoods = $goodsMap[$targetGId] ?? null;
                if (!$targetGoods) {
                    return ['state' => 0, 'msg' => '预补货商品不存在:' . $targetGId];
                }
                $isChangeGoods = $targetGId !== $beforeGId;
                if ($isChangeGoods && (int)($channel['frozen_stock'] ?? 0) > 0) {
                    return ['state' => 0, 'msg' => '当前货道有冻结库存，不允许更换商品:' . $machineId . '-' . $channel['mc_id']];
                }
                if ($isChangeGoods && (int)($channel['out_fail_stock'] ?? 0) > 0) {
                    return ['state' => 0, 'msg' => '当前货道有出货失败库存，不允许更换商品:' . $machineId . '-' . $channel['mc_id']];
                }
                $capacity = (int)$channel['capacity'];
                $stock = (int)$channel['stock'];
                $availableStock = $isChangeGoods
                    ? $capacity
                    : max(0, $capacity - $stock - (int)($channel['frozen_stock'] ?? 0));
                $planQuantity = (int)($item['plan_quantity'] ?? 0);
                if ($planQuantity > $availableStock) {
                    return ['state' => 0, 'msg' => '预补数量超过可补数量'];
                }

                $buildDetails[] = $this->makePreReplenishmentDetailRow(
                    $machine,
                    $channel,
                    $beforeGId,
                    (string)($original['before_sku'] ?? ($channel['sku'] ?? '')),
                    $targetGoods,
                    $stock,
                    $capacity,
                    $availableStock,
                    $planQuantity,
                    0,
                    0,
                    1
                );
                continue;
            }

            $queue = $queueResult['queues'][(int)$channel['mc_id']] ?? [];
            if (!$queue || isset($queueResult['errors'][(int)$channel['mc_id']])) {
                return ['state' => 0, 'msg' => '多商品货道批次队列异常，请刷新货道后重试'];
            }
            if ((int)$queue[0]['g_id'] !== (int)$channel['g_id']) {
                return ['state' => 0, 'msg' => '多商品货道队首与货道商品不一致，请先同步货道'];
            }
            $submittedByBatchId = [];
            foreach ($groupItems as $item) {
                $batchId = (int)($item['batch_id'] ?? 0);
                if ($batchId <= 0) {
                    return ['state' => 0, 'msg' => '多商品货道预补货必须传入batch_id'];
                }
                if (isset($submittedByBatchId[$batchId])) {
                    return ['state' => 0, 'msg' => '同一来源批次不允许重复'];
                }
                $submittedByBatchId[$batchId] = $item;
            }
            if (count($queue) !== count($submittedByBatchId)) {
                return ['state' => 0, 'msg' => '多商品货道必须提交包含队首和非队首的完整批次队列'];
            }

            foreach ($queue as $index => $batch) {
                if ((int)($batch['frozen_stock'] ?? 0) > 0) {
                    return ['state' => 0, 'msg' => '多商品货道存在冻结库存，暂不允许创建预补货单:' . $machineId . '-' . $channel['mc_id']];
                }
                $batchId = (int)$batch['batch_id'];
                if (!isset($submittedByBatchId[$batchId])) {
                    return ['state' => 0, 'msg' => '多商品货道必须提交包含队首和非队首的完整批次队列'];
                }
                $item = $submittedByBatchId[$batchId];
                $expectedSequence = $index + 1;
                if ((int)($item['batch_sequence'] ?? $expectedSequence) !== $expectedSequence) {
                    return ['state' => 0, 'msg' => '多商品货道批次顺序已发生变化，请刷新后重试'];
                }

                $original = $originalDetailMap['batch:' . $batchId] ?? [];
                $beforeGId = $original && (int)($original['before_g_id'] ?? 0) > 0
                    ? (int)$original['before_g_id']
                    : (int)$batch['g_id'];
                if ((int)$batch['g_id'] !== $beforeGId) {
                    return ['state' => 0, 'msg' => '来源批次商品已发生变化，请重新创建预补货单'];
                }
                $targetGId = $this->resolvePreReplenishmentTargetGId($item, $beforeGId);
                $targetGoods = $goodsMap[$targetGId] ?? null;
                if (!$targetGoods) {
                    return ['state' => 0, 'msg' => '预补货商品不存在:' . $targetGId];
                }

                $capacity = (int)$batch['capacity'];
                $stock = (int)$batch['stock'];
                $isChangeGoods = $targetGId !== $beforeGId;
                if ($index === 0 && $isChangeGoods && (int)($channel['out_fail_stock'] ?? 0) > 0) {
                    return ['state' => 0, 'msg' => '多商品货道队首有出货失败库存，不允许更换商品'];
                }
                $availableStock = $isChangeGoods ? $capacity : max(0, $capacity - $stock);
                $planQuantity = (int)($item['plan_quantity'] ?? 0);
                if ($planQuantity > $availableStock) {
                    return ['state' => 0, 'msg' => '批次预补数量超过可补数量'];
                }

                $buildDetails[] = $this->makePreReplenishmentDetailRow(
                    $machine,
                    $channel,
                    $beforeGId,
                    (string)($original['before_sku'] ?? ($batch['sku'] ?? '')),
                    $targetGoods,
                    $stock,
                    $capacity,
                    $availableStock,
                    $planQuantity,
                    $batchId,
                    $expectedSequence,
                    $index === 0 ? 1 : 2
                );
            }
        }

        return ['state' => 1, 'msg' => 'ok', 'details' => $buildDetails];
    }

    protected function resolvePreReplenishmentTargetGId($item, $beforeGId)
    {
        if (array_key_exists('after_g_id', $item)) {
            $afterGId = (int)$item['after_g_id'];
            return $afterGId > 0 ? $afterGId : (int)$beforeGId;
        }
        $targetGId = (int)($item['g_id'] ?? 0);
        return $targetGId > 0 ? $targetGId : (int)$beforeGId;
    }

    protected function makePreReplenishmentDetailRow(
        $machine,
        $channel,
        $beforeGId,
        $beforeSku,
        $targetGoods,
        $stock,
        $capacity,
        $availableStock,
        $planQuantity,
        $batchId,
        $batchSequence,
        $isHead
    ) {
        return [
            'm_id' => $machine['m_id'],
            'machine_id' => $machine['machine_id'],
            'mc_id' => $channel['mc_id'],
            'batch_id' => (int)$batchId,
            'target_batch_id' => 0,
            'batch_sequence' => (int)$batchSequence,
            'before_g_id' => (int)$beforeGId,
            'before_sku' => (string)$beforeSku,
            'g_id' => (int)$targetGoods['g_id'],
            'is_head' => (int)$isHead,
            'channel_code' => $channel['channel_code'],
            'sku' => (string)($targetGoods['sku'] ?? ''),
            'before_stock' => (int)$stock,
            'capacity' => (int)$capacity,
            'available_stock' => (int)$availableStock,
            'plan_quantity' => (int)$planQuantity,
            'actual_quantity' => null,
            'actual_sku' => null,
            'actual_channel_code' => null,
            'compare_status' => 1,
        ];
    }

    protected function getActiveChannelGoodsBatchMap($mcIds, $lock = false)
    {
        $mcIds = array_values(array_unique(array_filter(array_map('intval', (array)$mcIds))));
        $result = ['queues' => [], 'errors' => []];
        if (!$mcIds) {
            return $result;
        }

        $query = Db::name('channel_goods_batch')->alias('b')
            ->leftJoin('goods g', 'g.g_id = b.g_id')
            ->whereIn('b.mc_id', $mcIds)
            ->whereIn('b.status', [1, 2, 3])
            ->field('b.*,g.sku,g.g_name,g.pic,g.bar_code')
            ->order('b.mc_id asc,b.sequence asc,b.batch_id asc');
        if ($lock) {
            $query->lock(true);
        }
        $rows = $query->select()->toArray();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['mc_id']][] = $row;
        }

        foreach ($mcIds as $mcId) {
            $rowsForChannel = $grouped[$mcId] ?? [];
            $heads = array_values(array_filter($rowsForChannel, function ($row) {
                return (int)$row['status'] === 1;
            }));
            if (count($heads) !== 1) {
                $result['errors'][$mcId] = '多商品货道队首批次异常';
                continue;
            }
            $head = $heads[0];
            $headSequence = (int)$head['sequence'];
            $queue = [$head];
            foreach ($rowsForChannel as $row) {
                if ((int)$row['batch_id'] === (int)$head['batch_id']) {
                    continue;
                }
                if ((int)$row['sequence'] > $headSequence && in_array((int)$row['status'], [2, 3], true)) {
                    $queue[] = $row;
                }
            }
            usort($queue, function ($left, $right) {
                $sequenceCompare = (int)$left['sequence'] <=> (int)$right['sequence'];
                return $sequenceCompare !== 0
                    ? $sequenceCompare
                    : ((int)$left['batch_id'] <=> (int)$right['batch_id']);
            });
            $result['queues'][$mcId] = $queue;
        }

        return $result;
    }

    protected function makeRecordNo()
    {
        $recordNo = 'PR' . date('YmdHis') . mt_rand(10, 99);
        $exists = PreReplenishmentOrderModel::getCount(['record_no' => $recordNo]);
        if ($exists) {
            $recordNo = 'PR' . date('YmdHis') . mt_rand(100, 999);
        }
        return $recordNo;
    }

    // ==================== 单货道多商品相关开始 ====================
    protected function getInvalidPreReplenishmentReason($orderId)
    {
        $invalidMap = $this->getInvalidPreReplenishmentOrderMap([intval($orderId)]);
        return $invalidMap[intval($orderId)] ?? '';
    }

    protected function getInvalidPreReplenishmentOrderMap(array $orderIds)
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if (!$orderIds) {
            return [];
        }

        $details = Db::name('pre_replenishment_detail')
            ->whereIn('order_id', $orderIds)
            ->where('order_count', '<', 1)
            ->where(function ($query) {
                $query->where('batch_id', '>', 0)->whereOr('is_head', 2);
            })
            ->field('order_id,m_id,mc_id,batch_id,batch_sequence,before_g_id,g_id,is_head')
            ->select()
            ->toArray();
        if (!$details) {
            return [];
        }

        $mIds = array_values(array_unique(array_map('intval', array_column($details, 'm_id'))));
        $mcIds = array_values(array_unique(array_map('intval', array_column($details, 'mc_id'))));
        $configMap = Db::name('machine_config')->whereIn('m_id', $mIds)
            ->column('is_multi_goods', 'm_id');
        $channelMap = Db::name('machine_channel')->whereIn('mc_id', $mcIds)
            ->field('mc_id,is_multi_goods')->select()->toArray();
        $channelMap = array_column($channelMap, null, 'mc_id');
        $queueResult = $this->getActiveChannelGoodsBatchMap($mcIds);

        $invalidOrderIds = [];
        foreach ($details as $detail) {
            $orderId = (int)$detail['order_id'];
            $mId = (int)$detail['m_id'];
            $mcId = (int)$detail['mc_id'];
            if ((int)($configMap[$mId] ?? 2) !== 1
                || (int)($channelMap[$mcId]['is_multi_goods'] ?? 2) !== 1
                || isset($queueResult['errors'][$mcId])) {
                $invalidOrderIds[$orderId] = true;
                continue;
            }

            $queue = $queueResult['queues'][$mcId] ?? [];
            if ((int)($detail['batch_id'] ?? 0) > 0) {
                $batchMap = array_column($queue, null, 'batch_id');
                $batch = $batchMap[(int)$detail['batch_id']] ?? null;
                $batchPositionMap = [];
                foreach ($queue as $index => $queueBatch) {
                    $batchPositionMap[(int)$queueBatch['batch_id']] = $index + 1;
                }
                if (!$batch
                    || (int)$batch['g_id'] !== (int)($detail['before_g_id'] ?? 0)
                    || (int)($batchPositionMap[(int)$detail['batch_id']] ?? 0) !== (int)($detail['batch_sequence'] ?? 0)
                    || (((int)$detail['is_head'] === 1) !== ((int)$batch['status'] === 1))) {
                    $invalidOrderIds[$orderId] = true;
                }
                continue;
            }

            // 兼容旧的多商品预补货明细：旧数据没有 batch_id，仍按商品匹配非队首。
            $legacyMatched = false;
            foreach ($queue as $batch) {
                if ((int)$batch['status'] !== 1 && (int)$batch['g_id'] === (int)$detail['g_id']) {
                    $legacyMatched = true;
                    break;
                }
            }
            if (!$legacyMatched) {
                $invalidOrderIds[$orderId] = true;
            }
        }

        $invalidReason = '设备多商品配置或货道批次队列已发生变化，该预补货单已失效，请在后台手动完结';
        return array_fill_keys(array_map('intval', array_keys($invalidOrderIds)), $invalidReason);
    }
    // ==================== 单货道多商品相关结束 ====================

    protected function resolveCompareStatus($planQuantity, $actualQuantity)
    {
        if ($actualQuantity === null) {
            return 1;
        }
        if ($actualQuantity < $planQuantity) {
            return 3;
        }
        return 2;
    }

    protected function refreshOrderBizStatus($orderId)
    {
        $details = PreReplenishmentDetailModel::where(['order_id' => $orderId])
            ->field('machine_id,plan_quantity,actual_quantity,compare_status')
            ->select()
            ->toArray();
        if (!$details) {
            return true;
        }

        $hasPending = false;
        $hasLess = false;
        $hasMore = false;

        foreach ($details as $detail) {
            $actual = $detail['actual_quantity'];
            $plan = (int)$detail['plan_quantity'];
            if ($actual === null) {
                $hasPending = true;
                continue;
            }
            $actual = (int)$actual;
            if ($actual < $plan) {
                $hasLess = true;
            } elseif ($actual > $plan) {
                $hasMore = true;
            }
        }

        $orderStatus = 1;
        if ($hasMore) {
            $orderStatus = 4;
        } elseif ($hasLess) {
            $orderStatus = 3;
        } elseif (!$hasPending) {
            $orderStatus = 2;
        }

        return PreReplenishmentOrderModel::update([
            'id' => $orderId,
            'biz_status' => $orderStatus,
        ]);
    }

    protected function appendPreReplenishmentLogAndSync($recordNo, $logData)
    {
        $order = PreReplenishmentOrderModel::getFind(['record_no' => $recordNo], 'id,record_no');
        if (!$order) {
            return true;
        }

        $created = PreReplenishmentLogModel::create([
            'record_no' => $recordNo,
            'm_id' => $logData['m_id'] ?? 0,
            'machine_id' => $logData['machine_id'],
            'channel_code' => $logData['channel_code'],
            'sku' => $logData['sku'],
            'quantity' => $logData['quantity'],
            'report_time' => $logData['report_time'] ?? date('Y-m-d H:i:s'),
            'raw_payload' => $logData['raw_payload'] ?? '',
        ]);
        if (!$created) {
            return false;
        }

        $detail = PreReplenishmentDetailModel::where([
            ['order_id', '=', $order['id']],
            ['machine_id', '=', $logData['machine_id']],
            ['channel_code', '=', $logData['channel_code']],
            ['sku', '=', $logData['sku']],
        ])->lock(true)->find();

        if (!$detail) {
            $detailByChannel = PreReplenishmentDetailModel::where([
                ['order_id', '=', $order['id']],
                ['machine_id', '=', $logData['machine_id']],
                ['channel_code', '=', $logData['channel_code']],
                ['is_head', '=', 1],
            ])->lock(true)->find();
            if ($detailByChannel) {
                $actualQuantity = $detailByChannel['actual_quantity'] ?? 0;
                $newActualQuantity = $actualQuantity + $logData['quantity'];
                $saveResult = PreReplenishmentDetailModel::update([
                    'id' => $detailByChannel['id'],
                    'actual_quantity' => $newActualQuantity,
                    'actual_sku' => $logData['sku'],
                    'actual_channel_code' => $logData['channel_code'],
                    'compare_status' => 3,
                ]);
                if (!$saveResult) {
                    return false;
                }
            }

            return $this->refreshOrderBizStatus($order['id']) ? true : false;
        }

        $actualQuantity = $detail['actual_quantity'] ?? 0;
        $newActualQuantity = $actualQuantity + $logData['quantity'];
        $compareStatus = $this->resolveCompareStatus($detail['plan_quantity'], $newActualQuantity);

        $saveResult = PreReplenishmentDetailModel::update([
            'id' => $detail['id'],
            'actual_quantity' => $newActualQuantity,
            'actual_sku' => $logData['sku'],
            'actual_channel_code' => $logData['channel_code'],
            'compare_status' => $compareStatus,
        ]);
        if (!$saveResult) {
            return false;
        }

        return $this->refreshOrderBizStatus($order['id']) ? true : false;
    }

    protected function syncByTerminalReplenishmentRecordNo($recordNo, $mcData, $quantity, $rawPayload = [])
    {
        if (!$recordNo || !$mcData || $quantity == 0) {
            return true;
        }

        $machineId = $this->machine['machine_id'] ?? '';
        if (!$machineId) {
            return true;
        }

        $logData = [
            'm_id' => $this->machine['m_id'] ?? 0,
            'machine_id' => $machineId,
            'channel_code' => $mcData['channel_code'] ?? '',
            'g_id' => $mcData['g_id'] ?? 0,
            'sku' => $mcData['sku'] ?? '',
            'quantity' => $quantity,
            'report_time' => date('Y-m-d H:i:s'),
            'raw_payload' => arr2json($rawPayload),
        ];

        if (!$logData['channel_code'] || $logData['sku'] === '') {
            return true;
        }

        return $this->appendPreReplenishmentLogAndSync($recordNo, $logData);
    }

    /**
     * 查询预补货单据（给 getFind 走）
     */
    public function getMachinePreReplenishmentFind($where, $field = "*", $order = "")
    {
        return PreReplenishmentOrderModel::getFind($where, $field, $order);
    }

    /**
     * 查询预补货明细（给控制器用）
     */
    public function getMachinePreReplenishmentDetailFind($where, $field = "*", $order = "")
    {
        return PreReplenishmentDetailModel::getFind($where, $field, $order);
    }

    /**
     * MQ 回调：设备上报补货视频后保存地址
     * msgType: replenishmentVideo
     */
    public function replenishmentVideo()
    {
        $recordNo  = $this->message['record_no'] ?? '';
        $machineId = $this->machine['machine_id'] ?? '';
        $videoUrl  = $this->message['path'] ?? '';

        actionLog($this->message, "补货视频保存地址记录执行");

        if (!$recordNo || !$machineId || !$videoUrl || $videoUrl === 'no_data') {
            return 1;
        }

        $order = PreReplenishmentOrderModel::getFind(['record_no' => $recordNo], 'id');
        if (!$order) {
            return 1;
        }

        $where = ['order_id' => $order['id'], 'machine_id' => $machineId];
        $exists = PreReplenishmentVideoModel::getFind($where, 'id');

        if ($exists) {
            return PreReplenishmentVideoModel::update(
                ['replenishment_video' => $videoUrl, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $exists['id']]
            );
        }

        return PreReplenishmentVideoModel::create([
            'order_id' => $order['id'],
            'record_no' => $recordNo,
            'machine_id' => $machineId,
            'm_id' => $this->machine['m_id'] ?? 0,
            'replenishment_video' => $videoUrl,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
