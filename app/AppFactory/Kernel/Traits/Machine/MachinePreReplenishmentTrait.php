<?php

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Goods\GoodsModel;
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
        return array_values(array_filter($details, function ($item) {
            return is_array($item);
        }));
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

            $uniqueKey = $item['machine_id'] . '_' . $item['mc_id'];
            if (isset($uniqueMap[$uniqueKey])) {
                return ['state' => 0, 'msg' => '同一单内 machine_id + mc_id 不允许重复'];
            }
            $uniqueMap[$uniqueKey] = 1;
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
                ->field('m_id,mc_id,channel_code,sku,g_name,pic,stock,capacity')
                ->select()
                ->toArray();
        }

        $channelMap = [];
        foreach ($channels as $channel) {
            $channelMap[$channel['m_id'] . '_' . $channel['mc_id']] = $channel;
        }

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
            $availableStock = $channel['capacity'] - $channel['stock'];
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
                'channel_code' => $channel['channel_code'],
                'sku' => $channel['sku'],
                'before_stock' => $channel['stock'],
                'capacity' => $channel['capacity'],
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

        $updated = PreReplenishmentOrderModel::update([
            'id' => $orderId,
            'biz_status' => $orderStatus,
        ]);
        if (!$updated) return $updated;
        if ($orderStatus !== 1) {
            $this->releaseAutoFinishedPreOrderLockedStocks($orderId);
        }
        return $updated;
    }

    protected function releaseAutoFinishedPreOrderLockedStocks($orderId)
    {
        $order = PreReplenishmentOrderModel::where(['id' => intval($orderId)])
            ->field('id,record_no,ao_id')
            ->lock(true)
            ->find();
        if (!$order) throw new \Exception('预补货单不存在');

        $planRows = PreReplenishmentDetailModel::where(['order_id' => intval($orderId)])
            ->field('sku,SUM(plan_quantity) plan_quantity')
            ->group('sku')
            ->select()
            ->toArray();
        $issuedRows = Db::name('warehouse_trans_details')->alias('d')
            ->join('warehouse_trans t', 't.id = d.warehouse_trans_id')
            ->where(['t.record_no' => strval($order['record_no']), 't.type' => 4])
            ->field('d.sku,SUM(-d.changed) issued_quantity')
            ->group('d.sku')
            ->select()
            ->toArray();
        $issuedMap = [];
        foreach ($issuedRows as $row) $issuedMap[strval($row['sku'])] = intval($row['issued_quantity']);

        foreach ($planRows as $planRow) {
            $sku = strval($planRow['sku']);
            $releaseQuantity = max(0, intval($planRow['plan_quantity']) - intval($issuedMap[$sku] ?? 0));
            if ($releaseQuantity <= 0) continue;
            $goods = GoodsModel::where(['sku' => $sku])
                ->field('g_id,sku,locked_stocks')
                ->lock(true)
                ->find();
            if (!$goods) throw new \Exception('预补货商品SKU ' . $sku . ' 未关联goods商品');
            $goods = $goods->toArray();
            $beforeLocked = intval($goods['locked_stocks'] ?? 0);
            if ($releaseQuantity > $beforeLocked) throw new \Exception('商品SKU ' . $sku . ' 锁定库存释放数量异常');
            $afterLocked = $beforeLocked - $releaseQuantity;
            $updatedGoods = GoodsModel::where(['g_id' => intval($goods['g_id']), 'locked_stocks' => $beforeLocked])
                ->update(['locked_stocks' => $afterLocked]);
            if (!$updatedGoods) throw new \Exception('商品SKU ' . $sku . ' 锁定库存更新失败');

            $manager = isset($this->manager) && is_array($this->manager) ? $this->manager : [];
            $inserted = Db::name('goods_stock_lock_log')->insert([
                'business_event_key' => 'PR:AUTO_FINISH:' . strval($order['record_no']) . ':' . intval($goods['g_id']) . ':' . bin2hex(random_bytes(8)),
                'ao_id' => intval($order['ao_id'] ?? 0),
                'goods_id' => intval($goods['g_id']),
                'sku' => $sku,
                'record_no' => strval($order['record_no']),
                'order_id' => intval($orderId),
                'change_type' => 4,
                'change_quantity' => -$releaseQuantity,
                'before_locked_stocks' => $beforeLocked,
                'after_locked_stocks' => $afterLocked,
                'manager_id' => intval($manager['manager_id'] ?? 0),
                'manager_name' => strval($manager['nickname'] ?? ($manager['account'] ?? '设备确认补货')),
                'remark' => '设备确认后自动完结释放剩余锁定库存',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$inserted) throw new \Exception('商品SKU ' . $sku . ' 锁定库存流水写入失败');
        }
    }

    protected function appendPreReplenishmentLogAndSync($recordNo, $logData)
    {
        $order = PreReplenishmentOrderModel::where(['record_no' => $recordNo])
            ->field('id,record_no')->lock(true)->find();
        if (!$order) {
            return true;
        }

        $quantity = intval($logData['quantity'] ?? 0);
        if ($quantity <= 0) return false;
        $eventSource = strval($logData['event_id'] ?? '');
        if ($eventSource !== '') {
            $eventSource = implode('|', [$recordNo, strval($logData['machine_id'] ?? ''),
                strval($logData['channel_code'] ?? ''), strval($logData['sku'] ?? ''), $eventSource]);
        } else {
            $eventSource = implode('|', [$recordNo, strval($logData['machine_id'] ?? ''),
                strval($logData['channel_code'] ?? ''), strval($logData['sku'] ?? ''),
                strval($quantity), strval($logData['report_time'] ?? ''), strval($logData['raw_payload'] ?? '')]);
        }
        $eventKey = hash('sha256', $eventSource);
        if (PreReplenishmentLogModel::where(['event_key' => $eventKey])->count()) return true;

        $detail = PreReplenishmentDetailModel::where([
            ['order_id', '=', $order['id']],
            ['machine_id', '=', $logData['machine_id']],
            ['channel_code', '=', $logData['channel_code']],
            ['sku', '=', $logData['sku']],
        ])->lock(true)->find();
        if (!$detail) return false;

        $actualQuantity = intval($detail['actual_quantity'] ?? 0);
        $newActualQuantity = $actualQuantity + $quantity;
        if ($newActualQuantity > intval($detail['plan_quantity'])) return false;

        $created = PreReplenishmentLogModel::create([
            'event_key' => $eventKey,
            'record_no' => $recordNo,
            'm_id' => $logData['m_id'] ?? 0,
            'machine_id' => $logData['machine_id'],
            'channel_code' => $logData['channel_code'],
            'sku' => $logData['sku'],
            'quantity' => $quantity,
            'report_time' => $logData['report_time'] ?? date('Y-m-d H:i:s'),
            'raw_payload' => $logData['raw_payload'] ?? '',
        ]);
        if (!$created) {
            return false;
        }

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
            'event_id' => $rawPayload['msg_id'] ?? '',
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
