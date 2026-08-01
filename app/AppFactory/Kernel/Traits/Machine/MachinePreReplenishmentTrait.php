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
    protected function normalizeDetails($details)
    {
        $details = json2arr($details);
        if (!is_array($details)) {
            return [];
        }
        $details = array_values(array_filter($details, function ($item) {
            return is_array($item);
        }));

        // ==================== 单货道多商品相关开始 ====================
        $result = [];
        foreach ($details as $item) {
            $batchArr = json2arr($item['batch_arr'] ?? []);
            unset($item['batch_arr']);
            $item['is_head'] = 1;
            $result[] = $item;
            if (!is_array($batchArr)) {
                continue;
            }
            foreach ($batchArr as $batch) {
                if (!is_array($batch)) {
                    continue;
                }
                $result[] = [
                    'machine_id' => $item['machine_id'] ?? '',
                    'mc_id' => $item['mc_id'] ?? 0,
                    'g_id' => (int)($batch['g_id'] ?? 0),
                    'is_head' => 2,
                    'plan_quantity' => $batch['plan_quantity'] ?? 0,
                ];
            }
        }
        // ==================== 单货道多商品相关结束 ====================
        return $result;
    }

    protected function buildOrderDetails($details)
    {
        $uniqueMap = [];
        $machineIds = [];
        foreach ($details as $item) {
            if (!isset($item['machine_id']) || !isset($item['mc_id'])) {
                return ['state' => 0, 'msg' => '明细参数不完整'];
            }

            $planQuantity = $item['plan_quantity'] ?? 0;
            if ($planQuantity < 0) {
                return ['state' => 0, 'msg' => '预补数量不能小于0'];
            }

            $machineIds[$item['machine_id']] = $item['machine_id'];
        }

        $machines = MachineModel::where([['machine_id', 'in', array_values($machineIds)]])
            ->field('m_id,machine_id,machine_name')
            ->select()
            ->toArray();
        $machineMap = array_column($machines, null, 'machine_id');

        $mIds = array_column($machines, 'm_id');
        $channels = [];
        if ($mIds) {
            $channels = MachineChannelModel::where([['m_id', 'in', $mIds]])
                ->field('m_id,mc_id,channel_code,sku,g_name,pic,g_id,stock,capacity,is_multi_goods')
                ->select()
                ->toArray();
        }

        $channelMap = [];
        foreach ($channels as $channel) {
            $channelMap[$channel['m_id'] . '_' . $channel['mc_id']] = $channel;
        }

        // ==================== 单货道多商品相关开始 ====================
        $batchRows = [];
        $mcIds = array_values(array_unique(array_column($details, 'mc_id')));
        if ($mcIds) {
            $batchRows = Db::name('channel_goods_batch')->alias('b')
                ->leftJoin('goods g', 'g.g_id = b.g_id')
                ->whereIn('b.mc_id', $mcIds)
                ->whereIn('b.status', [2, 3])
                ->field('b.mc_id,b.g_id,b.stock,b.capacity,g.sku')
                ->order('b.sequence asc')
                ->select()->toArray();
        }
        $batchMap = [];
        foreach ($batchRows as $batch) {
            $batchKey = $batch['mc_id'] . '_' . $batch['g_id'];
            if (!isset($batchMap[$batchKey])) {
                $batchMap[$batchKey] = $batch;
            }
        }
        // ==================== 单货道多商品相关结束 ====================

        $buildDetails = [];
        foreach ($details as $item) {
            $machineId = $item['machine_id'];
            if (!isset($machineMap[$machineId])) {
                return ['state' => 0, 'msg' => '设备不存在:' . $machineId];
            }

            $machine = $machineMap[$machineId];
            $channelKey = $machine['m_id'] . '_' . $item['mc_id'];
            if (!isset($channelMap[$channelKey])) {
                return ['state' => 0, 'msg' => '货道不存在:' . $machineId . '-' . $item['mc_id']];
            }

            $channel = $channelMap[$channelKey];
            // ==================== 单货道多商品相关开始 ====================
            $isHead = (int)($item['is_head'] ?? 1) === 2 ? 2 : 1;
            $gId = (int)($item['g_id'] ?? 0);
            if ($isHead === 1) {
                if ($gId > 0 && $gId !== (int)$channel['g_id']) {
                    return ['state' => 0, 'msg' => '货道队首商品已发生变化，请刷新后重试'];
                }
                $gId = (int)$channel['g_id'];
                $stock = (int)$channel['stock'];
                $capacity = (int)$channel['capacity'];
                $sku = $channel['sku'];
            } else {
                if ((int)($channel['is_multi_goods'] ?? 2) !== 1 || $gId <= 0) {
                    return ['state' => 0, 'msg' => '货道非队首商品参数错误'];
                }
                $batch = $batchMap[$channel['mc_id'] . '_' . $gId] ?? null;
                if (!$batch) {
                    return ['state' => 0, 'msg' => '货道非队首商品不存在或状态已变化'];
                }
                $stock = (int)$batch['stock'];
                $capacity = (int)$batch['capacity'];
                $sku = $batch['sku'] ?? '';
            }

            $uniqueKey = $machineId . '_' . $channel['mc_id'] . '_' . $gId;
            if (isset($uniqueMap[$uniqueKey])) {
                return ['state' => 0, 'msg' => '同一货道内商品不允许重复'];
            }
            $uniqueMap[$uniqueKey] = 1;
            // ==================== 单货道多商品相关结束 ====================

            $availableStock = $capacity - $stock;
            if ($availableStock < 0) {
                $availableStock = 0;
            }

            if ($item['plan_quantity'] > $availableStock) {
                return ['state' => 0, 'msg' => '预补数量超过可补数量'];
            }

            $buildDetails[] = [
                'm_id' => $machine['m_id'],
                'machine_id' => $machine['machine_id'],
                'mc_id' => $channel['mc_id'],
                'g_id' => $gId,
                'is_head' => $isHead,
                'channel_code' => $channel['channel_code'],
                'sku' => $sku,
                'before_stock' => $stock,
                'capacity' => $capacity,
                'available_stock' => $availableStock,
                'plan_quantity' => $item['plan_quantity'],
                'actual_quantity' => null,
                'actual_sku' => null,
                'actual_channel_code' => null,
                'compare_status' => 1,
            ];
        }

        return ['state' => 1, 'msg' => 'ok', 'details' => $buildDetails];
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

        $invalidOrderIds = Db::name('pre_replenishment_detail')->alias('d')
            ->leftJoin('machine_config c', 'c.m_id = d.m_id')
            ->leftJoin('machine_channel mc', 'mc.mc_id = d.mc_id')
            ->leftJoin(
                'channel_goods_batch b',
                'b.mc_id = d.mc_id AND b.g_id = d.g_id AND b.status IN (2,3)'
            )
            ->whereIn('d.order_id', $orderIds)
            ->where('d.is_head', 2)
            ->whereRaw(
                '(IFNULL(c.is_multi_goods, 2) <> 1 '
                . 'OR IFNULL(mc.is_multi_goods, 2) <> 1 '
                . 'OR b.batch_id IS NULL)'
            )
            ->distinct(true)
            ->column('d.order_id');

        $invalidReason = '设备已关闭单货道多商品功能，该预补货单已失效，请在后台手动完结';
        return array_fill_keys(array_map('intval', $invalidOrderIds), $invalidReason);
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
