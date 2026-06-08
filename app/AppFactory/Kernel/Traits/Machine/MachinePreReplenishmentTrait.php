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
        $machineId = $this->message['machine_id'] ?? '';
        $videoUrl  = $this->message['replenishment_video'] ?? '';

        actionLog($this->message, "补货视频保存地址记录执行");

        if (!$recordNo || !$machineId || !$videoUrl) {
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
                ['replenishment_video' => $videoUrl],
                ['id' => $exists['id']]
            );
        }

        return PreReplenishmentVideoModel::create([
            'order_id' => $order['id'],
            'machine_id' => $machineId,
            'replenishment_video' => $videoUrl,
        ]);
    }
}
