<?php

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentDetailModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentLogModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentOrderModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentVideoModel;
use app\AppFactory\Kernel\Traits\Machine\MachinePreReplenishmentTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachinePreReplenishmentClient extends ManagementClient
{
    use MachinePreReplenishmentTrait;

    public function getMachineChannels($postData)
    {
        $machineIds = $postData['machine_ids'] ?? [];
        if (!is_array($machineIds)) {
            $machineIds = explode(',', (string)$machineIds);
        }
        $machineIds = array_values(array_filter($machineIds));
        if (!$machineIds) {
            return returnState(4001, '参数错误: machine_ids不能为空');
        }

        $orderId = $postData['order_id'] ?? 0;
        if ($orderId) {
            $editingOrder = PreReplenishmentOrderModel::getFind(['id' => $orderId], 'id,biz_status');
            if ($editingOrder && intval($editingOrder['biz_status']) === 1) {
                $invalidReason = $this->getInvalidPreReplenishmentReason($orderId);
                if ($invalidReason !== '') {
                    return returnState(4004, $invalidReason);
                }
            }
        }

        // 检查是否存在未补货的补货单
        $existingQuery = PreReplenishmentDetailModel::where([['machine_id', 'in', $machineIds]]);
        if ($orderId) {
            $existingQuery = $existingQuery->where('order_id', '<>', $orderId);
        }
        $existingDetails = $existingQuery->field('order_id')->group('order_id')->select()->toArray();
        if ($existingDetails) {
            $orderIds = array_column($existingDetails, 'order_id');
            $hasUnfinished = PreReplenishmentOrderModel::where([
                ['id', 'in', $orderIds],
                ['biz_status', '=', 1],
            ])->count();
            if ($hasUnfinished) {
                return returnState(4002, '已存在未补货的补货单');
            }
        }
        $orderMcIds = [];
        $orderDetailByChannel = [];
        $orderDetailByBatch = [];
        $selectedGoodsIds = [];
        if ($orderId) {
            $orderDetails = PreReplenishmentDetailModel::where(['order_id' => $orderId])
                ->field('m_id,machine_id,mc_id,batch_id,target_batch_id,batch_sequence,before_g_id,before_sku,g_id,sku,is_head,plan_quantity')
                ->select()
                ->toArray();
            foreach ($orderDetails as $d) {
                $mcKey = $d['m_id'] . '_' . $d['mc_id'];
                $orderMcIds[$mcKey] = true;
                if ((int)($d['batch_id'] ?? 0) > 0) {
                    $orderDetailByBatch[(int)$d['batch_id']] = $d;
                } else {
                    $orderDetailByChannel[$mcKey] = $d;
                }
                $selectedGoodsIds[] = $d['before_g_id'] ?? 0;
                $selectedGoodsIds[] = $d['g_id'] ?? 0;
            }
        }

        $machineList = MachineModel::where([['machine_id', 'in', $machineIds]])
            ->field('m_id,machine_id,machine_name')
            ->select()
            ->toArray();

        if (!$machineList) {
            return returnState(200, 'ok', ['machine_list' => []]);
        }

        $mIds = array_column($machineList, 'm_id');
        $machineMultiGoodsMap = [];
        $machineConfigRows = Db::name('machine_config')->whereIn('m_id', $mIds)
            ->field('m_id,is_multi_goods')->select()->toArray();
        foreach ($machineConfigRows as $configRow) {
            $machineMultiGoodsMap[(int)$configRow['m_id']] = (int)($configRow['is_multi_goods'] ?? 2) === 1;
        }

        $channelList = MachineChannelModel::where([['m_id', 'in', $mIds]])
            ->field('m_id,mc_id,channel_code,stock,frozen_stock,out_fail_stock,capacity,sku,g_name,pic,bar_code,g_id,is_multi_goods')
            ->order('mc_id asc')
            ->select()
            ->toArray();

        $multiMcIds = [];
        foreach ($channelList as $channel) {
            $selectedGoodsIds[] = $channel['g_id'] ?? 0;
            if (!empty($machineMultiGoodsMap[(int)$channel['m_id']])
                && (int)($channel['is_multi_goods'] ?? 2) === 1) {
                $multiMcIds[] = (int)$channel['mc_id'];
            }
        }
        $queueResult = $this->getActiveChannelGoodsBatchMap($multiMcIds);

        foreach ($queueResult['queues'] as $queue) {
            foreach ($queue as $batch) {
                $selectedGoodsIds[] = $batch['g_id'] ?? 0;
            }
        }
        $selectedGoodsMap = $this->getPreReplenishmentGoodsMap($selectedGoodsIds);

        $channelMap = [];
        $salesCache = [];
        foreach ($channelList as $channel) {
            $mcKey = $channel['m_id'] . '_' . $channel['mc_id'];
            $mcId = (int)$channel['mc_id'];
            $isMultiGoods = !empty($machineMultiGoodsMap[(int)$channel['m_id']])
                && (int)($channel['is_multi_goods'] ?? 2) === 1;

            if (!$isMultiGoods) {
                $orderDetail = $orderDetailByChannel[$mcKey] ?? [];
                $goodsContext = $this->resolvePreReplenishmentGoodsContext($orderDetail, $channel, $selectedGoodsMap);
                $availableStock = $goodsContext['is_change_goods']
                    ? (int)$channel['capacity']
                    : max(0, (int)$channel['capacity'] - (int)$channel['stock'] - (int)($channel['frozen_stock'] ?? 0));
                $canChangeGoods = (int)$channel['capacity'] > 0
                    && (int)($channel['frozen_stock'] ?? 0) <= 0
                    && (int)($channel['out_fail_stock'] ?? 0) <= 0;
                if ($availableStock <= 0 && !isset($orderMcIds[$mcKey]) && !$canChangeGoods) {
                    continue;
                }
                $targetGoods = $goodsContext['target_goods'];
                $channelMap[$channel['m_id']][] = [
                    'mc_id' => $mcId,
                    'channel_code' => $channel['channel_code'],
                    'batch_id' => 0,
                    'target_batch_id' => 0,
                    'batch_sequence' => 0,
                    'sequence' => 0,
                    'is_head' => 1,
                    'g_id' => $goodsContext['g_id'],
                    'after_g_id' => $goodsContext['g_id'],
                    'sku' => $targetGoods['sku'],
                    'g_name' => $targetGoods['g_name'],
                    'image_url' => $targetGoods['image_url'],
                    'bar_code' => $targetGoods['bar_code'],
                    'before_g_id' => $goodsContext['before_g_id'],
                    'before_sku' => $goodsContext['before_sku'],
                    'before_goods' => $goodsContext['before_goods'],
                    'target_goods' => $targetGoods,
                    'is_change_goods' => $goodsContext['is_change_goods'] ? 1 : 0,
                    'can_change_goods' => $canChangeGoods ? 1 : 0,
                    'before_stock' => (int)$channel['stock'],
                    'capacity' => (int)$channel['capacity'],
                    'available_stock' => $availableStock,
                    'plan_quantity' => (int)($orderDetail['plan_quantity'] ?? 0),
                    'is_multi_goods' => 2,
                    'batch_arr' => [],
                ];
                continue;
            }

            $queue = $queueResult['queues'][$mcId] ?? [];
            $batchError = $queueResult['errors'][$mcId] ?? '';
            if ($queue && (int)$queue[0]['g_id'] !== (int)$channel['g_id']) {
                $queue = [];
                $batchError = '多商品货道队首与货道商品不一致';
            }
            if (!$queue) {
                $channelMap[$channel['m_id']][] = [
                    'mc_id' => $mcId,
                    'channel_code' => $channel['channel_code'],
                    'is_multi_goods' => 1,
                    'batch_arr' => [],
                    'batch_error' => $batchError ?: '多商品货道批次队列异常',
                ];
                continue;
            }

            $batchArr = [];
            $queueFrozen = false;
            foreach ($queue as $index => $batch) {
                $batchId = (int)$batch['batch_id'];
                $orderDetail = $orderDetailByBatch[$batchId] ?? [];
                $sourceGoods = $selectedGoodsMap[(int)$batch['g_id']] ?? $batch;
                $goodsContext = $this->resolvePreReplenishmentGoodsContext($orderDetail, $sourceGoods, $selectedGoodsMap);
                $isChangeGoods = $goodsContext['is_change_goods'];
                $availableStock = $isChangeGoods
                    ? (int)$batch['capacity']
                    : max(0, (int)$batch['capacity'] - (int)$batch['stock'] - (int)$batch['frozen_stock']);
                $canChangeGoods = (int)$batch['capacity'] > 0
                    && (int)$batch['frozen_stock'] <= 0
                    && ($index !== 0 || (int)($channel['out_fail_stock'] ?? 0) <= 0);
                if ((int)$batch['frozen_stock'] > 0) {
                    $queueFrozen = true;
                }
                $targetGoods = $goodsContext['target_goods'];
                $batchArr[] = [
                    'batch_id' => $batchId,
                    'target_batch_id' => (int)($orderDetail['target_batch_id'] ?? 0),
                    'batch_sequence' => $index + 1,
                    'sequence' => $index + 1,
                    'status' => (int)$batch['status'],
                    'is_head' => $index === 0 ? 1 : 2,
                    'before_g_id' => $goodsContext['before_g_id'],
                    'before_sku' => $goodsContext['before_sku'],
                    'g_id' => $goodsContext['g_id'],
                    'after_g_id' => $goodsContext['g_id'],
                    'sku' => $targetGoods['sku'],
                    'g_name' => $targetGoods['g_name'],
                    'image_url' => $targetGoods['image_url'],
                    'bar_code' => $targetGoods['bar_code'],
                    'before_goods' => $goodsContext['before_goods'],
                    'target_goods' => $targetGoods,
                    'is_change_goods' => $isChangeGoods ? 1 : 0,
                    'can_change_goods' => $canChangeGoods ? 1 : 0,
                    'before_stock' => (int)$batch['stock'],
                    'frozen_stock' => (int)$batch['frozen_stock'],
                    'capacity' => (int)$batch['capacity'],
                    'available_stock' => $availableStock,
                    'plan_quantity' => (int)($orderDetail['plan_quantity'] ?? 0),
                ];
            }

            $headItem = $batchArr[0];
            $channelMap[$channel['m_id']][] = array_merge($headItem, [
                'mc_id' => $mcId,
                'channel_code' => $channel['channel_code'],
                'is_multi_goods' => 1,
                'can_pre_replenish' => $queueFrozen ? 2 : 1,
                'batch_error' => $queueFrozen ? '多商品货道存在冻结库存，暂不允许预补货' : '',
                'batch_arr' => $batchArr,
            ]);
        }

        $result = [];
        foreach ($machineList as $machine) {
            $mId = $machine['m_id'];
            $machineId = $machine['machine_id'];
            // 库存比
            $totalCapacity = MachineChannelModel::where('m_id', $mId)->where('g_id', '>', 0)->where('status', '<>', 2)->sum('capacity');
            $totalStock = MachineChannelModel::where('m_id', $mId)->where('g_id', '>', 0)->where('status', '<>', 2)->sum('stock');
            $stockRatio = '0%';
            if ($totalCapacity > 0) {
                $ratio = bcdiv((string)$totalStock, (string)$totalCapacity, 4);
                if (bccomp($ratio, '1', 4) > 0) $ratio = '1';
                if (bccomp($ratio, '0', 4) < 0) $ratio = '0';
                $stockRatio = bcmul($ratio, '100', 2) . '%';
            }
            // 货道近30天销售额
            $channels = $channelMap[$mId] ?? [];
            foreach ($channels as &$ch) {
                $gId = $ch['g_id'] ?? 0;
                $mcId = $ch['mc_id'] ?? 0;
                $salesKey = $machineId . '_' . $mcId . '_' . $gId;
                if (!isset($salesCache[$salesKey])) {
                    $salesCache[$salesKey] = ($gId && $mcId) ? $this->getSalesAmount($machineId, $gId, 30, $mcId) : 0;
                }
                $ch['sales_30_days'] = $salesCache[$salesKey];
            }
            unset($ch);
            $result[] = [
                'machine_id' => $machineId,
                'machine_name' => $machine['machine_name'],
                'stock_ratio' => $stockRatio,
                'total_capacity' => $totalCapacity,
                'total_stock' => $totalStock,
                'channels' => $channels,
            ];
        }

        return returnState(200, 'ok', ['machine_list' => $result]);
    }

    public function getOrderInfo($where, $field = '*')
    {
        return PreReplenishmentOrderModel::getFind($where, $field);
    }

    public function addOrder($postData)
    {
        $details = $this->normalizeDetails($postData['details'] ?? []);
        if (!$details) {
            return returnState(4001, '参数错误: 明细不能为空');
        }

        $checkResult = $this->buildOrderDetails($details);
        if ($checkResult['state'] !== 1) {
            return returnState(4001, $checkResult['msg']);
        }

        $creatorId = $this->manager['manager_id'] ?? 0;
        $creatorName = $this->manager['nickname'] ?? '';
        $recordNo = $this->makeRecordNo();

        Db::startTrans();
        try {
            $orderId = PreReplenishmentOrderModel::insertGetId([
                'record_no' => $recordNo,
                'ao_id' => $this->manager['ao_id'] ?? 0,
                'creator_id' => $creatorId,
                'creator_name' => $creatorName,
                'remark' => $postData['remark'] ?? '',
                'biz_status' => 1,
                'export_status' => 0,
            ]);

            $insertDetails = [];
            foreach ($checkResult['details'] as $detail) {
                $detail['order_id'] = $orderId;
                $insertDetails[] = $detail;
            }
            $insertResult = true;
            if ($insertDetails) {
                $insertResult = PreReplenishmentDetailModel::insertAll($insertDetails);
            }

            if (!$orderId || !$insertResult) {
                Db::rollback();
                return returnState(5000, '系统错误');
            }

            Db::commit();
            return returnState(200, '创建成功', [
                'id' => $orderId,
                'record_no' => $recordNo,
                'biz_status' => 1,
                'export_status' => 0,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1);
            return returnState(5000, '系统错误');
        }
    }

    public function updateOrder($postData)
    {
        $id = $postData['id'] ?? 0;
        if (!$id) {
            return returnState(4001, '参数错误: id不能为空');
        }

        $order = PreReplenishmentOrderModel::getFind(['id' => $id], 'id,record_no,biz_status');
        if (!$order) {
            return returnState(4003, '单据不存在');
        }
        if (intval($order['biz_status']) !== 1) {
            return returnState(4004, '只有未完成的预补货单允许修改');
        }
        if (intval($order['biz_status']) === 1) {
            $invalidReason = $this->getInvalidPreReplenishmentReason($id);
            if ($invalidReason !== '') {
                return returnState(4004, $invalidReason);
            }
        }

        $logCount = PreReplenishmentLogModel::getCount(['record_no' => $order['record_no']]);
        if ($logCount > 0) {
            return returnState(4004, '该补货单已进行补货，不允许修改');
        }
        $confirmedCount = PreReplenishmentDetailModel::where(['order_id' => $id])
            ->where('order_count', '>=', 1)
            ->count();
        if ($confirmedCount > 0) {
            return returnState(4004, '该补货单已确认或手动完结，不允许修改');
        }

        $details = $this->normalizeDetails($postData['details'] ?? []);
        if (!$details) {
            return returnState(4001, '参数错误: 明细不能为空');
        }

        $originalDetails = PreReplenishmentDetailModel::where(['order_id' => $id])
            ->field('machine_id,mc_id,batch_id,batch_sequence,before_g_id,before_sku,g_id,sku,is_head')
            ->select()
            ->toArray();
        $originalDetailMap = [];
        foreach ($originalDetails as $originalDetail) {
            if ((int)($originalDetail['batch_id'] ?? 0) > 0) {
                $originalDetailMap['batch:' . (int)$originalDetail['batch_id']] = $originalDetail;
            } else {
                $originalDetailMap['channel:' . $originalDetail['machine_id'] . '_' . $originalDetail['mc_id']] = $originalDetail;
            }
        }

        $checkResult = $this->buildOrderDetails($details, $originalDetailMap);
        if ($checkResult['state'] !== 1) {
            return returnState(4001, $checkResult['msg']);
        }

        Db::startTrans();
        try {
            $updateResult = PreReplenishmentOrderModel::update([
                'id' => $id,
                'remark' => $postData['remark'] ?? '',
                'biz_status' => 1,
            ]);

            PreReplenishmentDetailModel::whereDel(['order_id' => $id]);

            $insertDetails = [];
            foreach ($checkResult['details'] as $detail) {
                $detail['order_id'] = $id;
                $insertDetails[] = $detail;
            }
            $insertResult = true;
            if ($insertDetails) {
                $insertResult = PreReplenishmentDetailModel::insertAll($insertDetails);
            }

            if (!$updateResult || !$insertResult) {
                Db::rollback();
                return returnState(5000, '系统错误');
            }

            Db::commit();
            return returnState(200, '修改成功', ['id' => $id]);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1);
            return returnState(5000, '系统错误');
        }
    }

    public function getOrderList($postData,$where = [])
    {
        $page = $postData['page'] ?? 1;
        $pageSize = $postData['page_size'] ?? 20;

        if (!empty($postData['record_no'])) {
            $where[] = ['record_no', 'like', '%' . $postData['record_no'] . '%'];
        }
        if (!empty($postData['biz_status'])) {
            $where[] = ['biz_status', '=', $postData['biz_status']];
        }
        if (isset($postData['export_status']) && $postData['export_status'] !== '') {
            $where[] = ['export_status', '=', $postData['export_status']];
        }
        if (!empty($postData['creator_kw'])) {
            $where[] = ['creator_name', 'like', '%' . $postData['creator_kw'] . '%'];
        }
        if (!empty($postData['start_time'])) {
            $where[] = ['created_at', '>=', $postData['start_time']];
        }
        if (!empty($postData['end_time'])) {
            $where[] = ['created_at', '<=', $postData['end_time']];
        }

        $listModel = PreReplenishmentOrderModel::where($where)->order('id desc')->paginate($pageSize, false, ['page' => $page]);
        $list = $listModel->items();

        if (!$list) {
            return returnState(200, 'ok', [
                'list' => [],
                'total' => 0,
                'page' => $page,
                'page_size' => $pageSize,
            ]);
        }

        $orderIds = array_column($list, 'id');
        $unfinishedOrderIds = [];
        foreach ($list as $item) {
            if (intval($item['biz_status']) === 1) {
                $unfinishedOrderIds[] = intval($item['id']);
            }
        }
        $invalidMap = $this->getInvalidPreReplenishmentOrderMap($unfinishedOrderIds);
        $detailRows = PreReplenishmentDetailModel::where([['order_id', 'in', $orderIds]])
            ->field('order_id,machine_id,sku,plan_quantity')
            ->select()
            ->toArray();

        $summaryMap = [];
        foreach ($detailRows as $row) {
            if (!isset($summaryMap[$row['order_id']])) {
                $summaryMap[$row['order_id']] = [
                    'machine_names' => [],
                    'sku_map' => [],
                    'plan_total' => 0,
                ];
            }
            if ($row['machine_id']) {
                $summaryMap[$row['order_id']]['machine_names'][$row['machine_id']] = $row['machine_id'];
            }
            if ($row['sku'] && (int)$row['plan_quantity'] > 0) {
                $summaryMap[$row['order_id']]['sku_map'][$row['sku']] = $row['sku'];
            }
            $summaryMap[$row['order_id']]['plan_total'] += $row['plan_quantity'];
        }

        $result = [];
        foreach ($list as $item) {
            $summary = $summaryMap[$item['id']] ?? ['machine_names' => [], 'sku_map' => [], 'plan_total' => 0];
            $result[] = [
                'id' => $item['id'],
                'record_no' => $item['record_no'],
                'biz_status' => $item['biz_status'],
                'export_status' => $item['export_status'],
                'creator_name' => $item['creator_name'],
                'created_at' => $item['created_at'],
                'export_time' => $item['export_time'],
                'machine_names' => array_values($summary['machine_names']),
                'sku_count' => count($summary['sku_map']),
                'plan_total' => $summary['plan_total'],
                'is_invalid' => isset($invalidMap[$item['id']]) ? 1 : 2,
                'invalid_reason' => $invalidMap[$item['id']] ?? '',
            ];
        }

        return returnState(200, 'ok', [
            'list' => $result,
            'total' => $listModel->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    public function getOrderDetail($postData)
    {
        $id = $postData['id'] ?? 0;
        if (!$id) {
            return returnState(4001, '参数错误: id不能为空');
        }

        $order = PreReplenishmentOrderModel::getFind(['id' => $id]);
        if (!$order) {
            return returnState(4003, '单据不存在');
        }
        $invalidReason = intval($order['biz_status']) === 1
            ? $this->getInvalidPreReplenishmentReason($id)
            : '';

        $details = PreReplenishmentDetailModel::where(['order_id' => $id])->order('id asc')->select()->toArray();
        $logs = PreReplenishmentLogModel::where(['record_no' => $order['record_no']])->order('id asc')->select()->toArray();

        // 批量查询设备和货道信息
        $machineIds = array_values(array_unique(array_column($details, 'machine_id')));
        $machineMap = [];
        if ($machineIds) {
            $machines = MachineModel::where([['machine_id', 'in', $machineIds]])
                ->field('m_id,machine_id,machine_name')
                ->select()->toArray();
            foreach ($machines as $m) {
                $machineMap[$m['machine_id']] = $m;
            }
        }

        $mcIds = array_values(array_unique(array_column($details, 'mc_id')));
        $channelMap = [];
        if ($mcIds) {
            $channels = MachineChannelModel::where([['mc_id', 'in', $mcIds]])
                ->field('mc_id,m_id,machine_id,channel_code,sku,g_name,pic,g_id,bar_code')
                ->select()->toArray();
            foreach ($channels as $ch) {
                $channelMap[$ch['mc_id']] = $ch;
            }
        }

        // ==================== 单货道多商品相关开始 ====================
        $detailGoodsMap = [];
        $detailGoodsIds = [];
        foreach ($details as $detail) {
            $detailGoodsIds[] = $detail['before_g_id'] ?? 0;
            $detailGoodsIds[] = $detail['g_id'] ?? 0;
        }
        $detailGoodsIds = array_values(array_filter(array_unique($detailGoodsIds)));
        if ($detailGoodsIds) {
            $detailGoods = GoodsModel::where([['g_id', 'in', $detailGoodsIds]])
                ->field('g_id,sku,g_name,pic,bar_code')
                ->select()->toArray();
            $detailGoodsMap = array_column($detailGoods, null, 'g_id');
        }
        // ==================== 单货道多商品相关结束 ====================

        // 日志按 device+machine 统计 reported_count
        $logReportedMap = [];
        foreach ($logs as $log) {
            $key = $log['machine_id'];
            if (!isset($logReportedMap[$key])) $logReportedMap[$key] = 0;
            $logReportedMap[$key]++;
        }

        // 按机器聚合 device_progress
        $deviceProgressMap = [];
        $planTotal = 0;
        $actualTotal = 0;
        $abnormalTotal = 0;

        // 按 SKU 聚合 material_details
        $materialMap = [];

        // 补货对比明细 (按 machine_id 分组)
        $compareGroups = [];
        $salesCache = [];

        foreach ($details as $d) {
            $mid     = $d['machine_id'];
            $mcId    = $d['mc_id'];
            $channel = $channelMap[$mcId] ?? [];
            $sourceGoods = $detailGoodsMap[(int)($d['before_g_id'] ?? 0)] ?? $channel;
            $goodsContext = $this->resolvePreReplenishmentGoodsContext($d, $sourceGoods, $detailGoodsMap);
            $beforeGoods = $goodsContext['before_goods'];
            $goods = $goodsContext['target_goods'];
            $gId = (int)$goodsContext['g_id'];
            $machine = $machineMap[$mid] ?? [];
            $planQty = (int)$d['plan_quantity'];
            $actualQty = isset($d['actual_quantity']) ? (int)$d['actual_quantity'] : null;

            // ---- device_progress ----
            if (!isset($deviceProgressMap[$mid])) {
                $deviceProgressMap[$mid] = [
                    'm_id'          => $machine['m_id'] ?? 0,
                    'machine_id'    => $mid,
                    'machine_name'  => $machine['machine_name'] ?? $mid,
                    'detail_count'  => 0,
                    'plan_quantity'  => 0,
                    'actual_quantity'=> 0,
                    'abnormal_count' => 0,
                ];
            }
            $deviceProgressMap[$mid]['detail_count']++;
            $deviceProgressMap[$mid]['plan_quantity'] += $planQty;
            $deviceProgressMap[$mid]['actual_quantity'] += ($actualQty ?? 0);

            // ---- abnormal count ----
            $isAbnormal = ($actualQty !== null && $actualQty !== $planQty)
                || ($actualQty !== null && ($d['actual_sku'] ?? null) !== null && $d['actual_sku'] !== $d['sku'])
                || ($actualQty !== null && ($d['actual_channel_code'] ?? null) !== null && $d['actual_channel_code'] !== $d['channel_code']);
            if ($isAbnormal) {
                $deviceProgressMap[$mid]['abnormal_count']++;
                $abnormalTotal++;
            }

            // 找出最新的上报时间
            $reportTime = null;
            foreach ($logs as $log) {
                if ($log['machine_id'] === $mid
                    && ($log['channel_code'] ?? '') === ($d['channel_code'] ?? '')
                    && ($log['sku'] ?? '') === ($d['sku'] ?? '')) {
                    $reportTime = $log['report_time'];
                }
            }

            // ---- compare_status 文本映射 ----
            $availableStock = isset($d['available_stock'])
                ? (int)$d['available_stock']
                : (($d['capacity'] ?? 0) - ($d['before_stock'] ?? 0));
            if ($availableStock < 0) $availableStock = 0;

            $compareStatusText = 'pending';
            if ($actualQty !== null) {
                if ($actualQty == $planQty) {
                    $compareStatusText = 'matched';
                } elseif ($actualQty < $planQty) {
                    $compareStatusText = 'less';
                } else {
                    $compareStatusText = 'more';
                }
                if (($d['actual_sku'] ?? null) !== null && $d['actual_sku'] !== $d['sku']) {
                    $compareStatusText = 'sku_error';
                }
                if (($d['actual_channel_code'] ?? null) !== null && $d['actual_channel_code'] !== $d['channel_code']) {
                    $compareStatusText = 'channel_error';
                }
            }

            // ---- replenishment_compare detail ----
            $gId = $gId ?: (int)($channel['g_id'] ?? 0);
            $salesKey = $mid . '_' . $mcId . '_' . $gId;
            if (!isset($salesCache[$salesKey])) {
                $salesCache[$salesKey] = ($gId && $mcId) ? $this->getSalesAmount($mid, $gId, 30, $mcId) : 0;
            }
            $compareDetail = [
                'id'                 => $d['id'],
                'm_id'               => $machine['m_id'] ?? 0,
                'mc_id'              => $mcId,
                'batch_id'           => (int)($d['batch_id'] ?? 0),
                'target_batch_id'    => (int)($d['target_batch_id'] ?? 0),
                'batch_sequence'     => (int)($d['batch_sequence'] ?? 0),
                'g_id'               => $gId,
                'after_g_id'         => $gId,
                'before_g_id'        => (int)$goodsContext['before_g_id'],
                'before_sku'         => $goodsContext['before_sku'] ?: $beforeGoods['sku'],
                'is_head'            => (int)($d['is_head'] ?? 1),
                'machine_id'         => $mid,
                'machine_name'       => $machine['machine_name'] ?? $mid,
                'channel_code'       => $d['channel_code'],
                'sku'                => $d['sku'],
                'g_name'             => $goods['g_name'] ?? '',
                'image_url'          => $goods['pic'] ?? '',
                'bar_code'           => $goods['bar_code'] ?? '',
                'before_goods'       => $beforeGoods,
                'target_goods'       => $goods,
                'is_change_goods'    => $goodsContext['is_change_goods'] ? 1 : 0,
                'model'              => '',
                'before_stock'       => $d['before_stock'] ?? 0,
                'capacity'           => $d['capacity'] ?? 0,
                'available_stock'    => $availableStock,
                'plan_quantity'      => $planQty,
                'actual_quantity'    => $actualQty,
                'actual_sku'         => $d['actual_sku'] ?? null,
                'actual_channel_code'=> $d['actual_channel_code'] ?? null,
                'report_time'        => $reportTime,
                'compare_status'     => $compareStatusText,
                'sales_30_days'      => $salesCache[$salesKey],
            ];

            if (!isset($compareGroups[$mid])) {
                $compareGroups[$mid] = [];
            }
            $compareGroups[$mid][] = $compareDetail;

            // ---- material_details (按 g_id 聚合) ----
            if ($planQty > 0) {
                $gIdKey = $gId ? (string)$gId : ($d['sku'] ?? '');
                if (!isset($materialMap[$gIdKey])) {
                    $materialMap[$gIdKey] = [
                        'sku'          => $goods['sku'] ?? $d['sku'] ?? '',
                        'g_name'       => $goods['g_name'] ?? '',
                        'image_url'    => $goods['pic'] ?? '',
                        'bar_code'     => $goods['bar_code'] ?? '',
                        'model'        => '',
                        'quantity'     => 0,
                        'device_count' => 0,
                        'channel_count'=> 0,
                        '_device_ids'  => [],
                    ];
                }
                $materialMap[$gIdKey]['quantity'] += $planQty;
                $materialMap[$gIdKey]['channel_count']++;
                $materialMap[$gIdKey]['_device_ids'][$mid] = true;
            }

            $planTotal += $planQty;
            $actualTotal += ($actualQty ?? 0);
        }

        // material_details 最终 device_count
        foreach ($materialMap as &$m) {
            $m['device_count'] = count($m['_device_ids']);
            unset($m['_device_ids']);
        }
        unset($m);

        // ---- device_progress 最终结果 ----
        $deviceProgress = [];
        foreach ($deviceProgressMap as $mid => $dp) {
            $reportedCount = $logReportedMap[$mid] ?? 0;
            if ($dp['abnormal_count'] > 0) {
                $result = 'abnormal';
            } elseif ($dp['actual_quantity'] >= $dp['plan_quantity'] && $dp['actual_quantity'] > 0) {
                $result = 'completed';
            } elseif ($reportedCount > 0) {
                $result = 'pending';
            } else {
                $result = 'pending';
            }
            // 更精确的判断: 如果没有数据上报且没有实际数量, 为 pending
            if ($dp['actual_quantity'] == 0 && $reportedCount == 0) {
                $result = 'pending';
            } elseif ($dp['abnormal_count'] > 0) {
                $result = 'abnormal';
            } elseif ($dp['plan_quantity'] <= $dp['actual_quantity']) {
                $result = 'completed';
            }

            $deviceProgress[] = [
                'm_id'            => $dp['m_id'],
                'machine_id'      => $dp['machine_id'],
                'machine_name'    => $dp['machine_name'],
                'detail_count'    => $dp['detail_count'],
                'plan_quantity'   => $dp['plan_quantity'],
                'actual_quantity' => $dp['actual_quantity'],
                'abnormal_count'  => $dp['abnormal_count'],
                'reported_count'  => $reportedCount,
                'result'          => $result,
            ];
        }

        // ---- replenishment_compare (补货对比) ----
        $replenishmentCompare = [];
        foreach ($compareGroups as $mid => $groupDetails) {
            $dp = $deviceProgressMap[$mid];
            $reportedCount = $logReportedMap[$mid] ?? 0;
            if ($dp['abnormal_count'] > 0) {
                $result = 'abnormal';
            } elseif ($dp['actual_quantity'] >= $dp['plan_quantity']) {
                $result = 'completed';
            } else {
                $result = 'pending';
            }
            // 库存比
            $mId = $dp['m_id'] ?? 0;
            $totalCapacity = MachineChannelModel::where('m_id', $mId)->where('status', '<>', 2)->sum('capacity');
            $totalStock = MachineChannelModel::where('m_id', $mId)->where('status', '<>', 2)->sum('stock');
            $stockRatio = '0%';
            if ($totalCapacity > 0) {
                $ratio = bcdiv((string)$totalStock, (string)$totalCapacity, 4);
                if (bccomp($ratio, '1', 4) > 0) $ratio = '1';
                if (bccomp($ratio, '0', 4) < 0) $ratio = '0';
                $stockRatio = bcmul($ratio, '100', 2) . '%';
            }
            $replenishmentCompare[] = [
                'm_id'            => $dp['m_id'],
                'machine_id'      => $dp['machine_id'],
                'machine_name'    => $dp['machine_name'],
                'detail_count'    => $dp['detail_count'],
                'plan_quantity'   => $dp['plan_quantity'],
                'actual_quantity' => $dp['actual_quantity'],
                'abnormal_count'  => $dp['abnormal_count'],
                'reported_count'  => $reportedCount,
                'result'          => $result,
                'stock_ratio'     => $stockRatio,
                'details'         => $groupDetails,
            ];
        }

        // ---- can_edit ----
        $logCount = PreReplenishmentLogModel::getCount(['record_no' => $order['record_no']]);
        $confirmedCount = PreReplenishmentDetailModel::where(['order_id' => $id])
            ->where('order_count', '>=', 1)
            ->count();
        $canEdit = ((int)$order['biz_status'] === 1
            && $logCount == 0
            && $confirmedCount == 0
            && $invalidReason === '');

        // ---- summary ----
        $summary = [
            'device_count'   => count($deviceProgress),
            'channel_count'  => count(array_unique(array_map(function ($detail) {
                return $detail['machine_id'] . '_' . $detail['mc_id'];
            }, $details))),
            'plan_total'     => $planTotal,
            'actual_total'   => $actualTotal,
            'abnormal_count' => $abnormalTotal,
        ];

        return returnState(200, 'ok', [
            'id'                   => $order['id'],
            'record_no'            => $order['record_no'],
            'status'               => $order['biz_status'],
            'creator'              => $order['creator_name'],
            'create_time'          => $order['created_at'],
            'export_time'          => $order['export_time'],
            'remark'               => $order['remark'],
            'can_edit'             => $canEdit,
            'can_finish'           => intval($order['biz_status']) === 1,
            'is_invalid'           => $invalidReason !== '' ? 1 : 2,
            'invalid_reason'       => $invalidReason,
            'summary'              => $summary,
            'device_progress'      => $deviceProgress,
            'material_details'     => array_values($materialMap),
            'replenishment_compare'=> $replenishmentCompare,
        ]);
    }

    public function exportOrder($postData)
    {
        $orderIds = $postData['order_ids'] ?? [];
        if (!is_array($orderIds)) {
            $orderIds = explode(',', (string)$orderIds);
        }
        $orderIds = array_values(array_filter($orderIds));
        if (!$orderIds) {
            return returnState(4001, '参数错误: order_ids不能为空');
        }

        $details = PreReplenishmentDetailModel::where([['order_id', 'in', $orderIds]])
            ->field('id,order_id,m_id,machine_id,mc_id,g_id,is_head,channel_code,sku,plan_quantity,actual_quantity,compare_status')
            ->order('id asc')
            ->select()
            ->toArray();
        if (!$details) {
            return returnState(4003, '单据不存在或无明细');
        }

        $orders = PreReplenishmentOrderModel::where([['id', 'in', $orderIds]])
            ->field('id,record_no,export_status,creator_name,created_at')
            ->select()
            ->toArray();
        if (!$orders) {
            return returnState(4003, '单据不存在');
        }
        $orderMap = array_column($orders, null, 'id');

        $mids = array_values(array_unique(array_column($details, 'm_id')));
        $mcs = array_values(array_unique(array_column($details, 'mc_id')));
        $channelMap = [];
        if ($mids && $mcs) {
            $channels = MachineChannelModel::where([
                ['m_id', 'in', $mids],
                ['mc_id', 'in', $mcs],
            ])->field('m_id,mc_id,g_name,pic,g_id')->select()->toArray();
            foreach ($channels as $channel) {
                $channelMap[$channel['m_id'] . '_' . $channel['mc_id']] = $channel;
            }
        }

        // ==================== 单货道多商品相关开始 ====================
        $exportGoodsMap = [];
        $exportGoodsIds = array_values(array_filter(array_unique(array_column($details, 'g_id'))));
        if ($exportGoodsIds) {
            $exportGoods = GoodsModel::where([['g_id', 'in', $exportGoodsIds]])
                ->field('g_id,g_name,pic,sku')
                ->select()->toArray();
            $exportGoodsMap = array_column($exportGoods, null, 'g_id');
        }
        // ==================== 单货道多商品相关结束 ====================

        $groupRows = [];
        foreach ($details as $row) {
            if ((int)($row['plan_quantity'] ?? 0) <= 0) {
                continue;
            }
            $order = $orderMap[$row['order_id']] ?? [];
            $channel = $channelMap[$row['m_id'] . '_' . $row['mc_id']] ?? [];
            $gId = (int)($row['g_id'] ?? 0) ?: (int)($channel['g_id'] ?? 0);
            $goods = $exportGoodsMap[$gId] ?? $channel;
            $groupKey = ($order['record_no'] ?? '') . '|' . $gId;
            if (!isset($groupRows[$groupKey])) {
                $recordNo = trim((string)($order['record_no'] ?? ''));
                $groupRows[$groupKey] = [
                    'record_no' => $order['record_no'] ?? '',
                    'order_bar_code_image' => $recordNo === '' ? '' : ('https://bwipjs-api.metafloor.com/?bcid=code128&includetext=true&scale=2&text=' . urlencode($recordNo)),
                    'goods_name' => $goods['g_name'] ?? '',
                    'goods_pic' => $goods['pic'] ?? '',
                    'sku' => $row['sku'] ?? '',
                    'plan_quantity' => 0,
                    'actual_quantity' => 0,
                    'actual_has_value' => false,
                    'creator_name' => $order['creator_name'] ?? '',
                    'created_at' => $order['created_at'] ?? '',
                ];
            }

            if ($groupRows[$groupKey]['goods_name'] === '' && !empty($goods['g_name'])) {
                $groupRows[$groupKey]['goods_name'] = $goods['g_name'];
            }
            if ($groupRows[$groupKey]['goods_pic'] === '' && !empty($goods['pic'])) {
                $groupRows[$groupKey]['goods_pic'] = $goods['pic'];
            }

            $groupRows[$groupKey]['plan_quantity'] += (int)($row['plan_quantity'] ?? 0);
            if ($row['actual_quantity'] !== null && $row['actual_quantity'] !== '') {
                $groupRows[$groupKey]['actual_has_value'] = true;
                $groupRows[$groupKey]['actual_quantity'] += (int)$row['actual_quantity'];
            }
        }

        $recordNoForHeader = '';
        $recordBarCodeImageForHeader = '';
        $list = [];
        foreach ($groupRows as $item) {
            if ($recordNoForHeader === '') {
                $recordNoForHeader = $item['record_no'] ?? '';
            }
            if ($recordBarCodeImageForHeader === '') {
                $recordBarCodeImageForHeader = $item['order_bar_code_image'] ?? '';
            }
            $list[] = [
                'col_a' => $item['goods_name'] ?? '',
                'col_b' => $item['goods_pic'] ?? '',
                'col_c' => $item['sku'] ?? '',
                'col_d' => (int)($item['plan_quantity'] ?? 0),
                'col_e' => $item['creator_name'] ?? '',
                'col_f' => $item['created_at'] ?? '',
            ];
        }

        // 第2行: 补货码数据与补货码图片
        $title = [
            'col_a' => $recordNoForHeader,
            'col_b' => '',
            'col_c' => '',
            'col_d' => $recordBarCodeImageForHeader,
            'col_e' => '',
            'col_f' => '',
        ];
        // 第3行: 商品标题
        array_unshift($list, [
            'col_a' => '商品名称',
            'col_b' => '商品图片',
            'col_c' => 'SKU',
            'col_d' => '领料数量',
            'col_e' => '创建人',
            'col_f' => '创建时间',
        ]);
        $filename = '预补货领料表-' . date('YmdHis');

        // Sheet 1: 汇总数据（保持现有格式）
        $sheets = [
            [
                'sheetName' => '领料汇总',
                'title' => $title,
                'list' => $list,
                'merge' => [
                    [
                        'merge' => 'A1:C1',
                        'cell' => 'A1',
                        'name' => '补货编号',
                    ],
                    [
                        'merge' => 'D1:F1',
                        'cell' => 'D1',
                        'name' => '补货条形码',
                    ],
                    [
                        'merge' => 'A2:C2',
                        'cell' => 'A2',
                        'name' => '',
                    ],
                    [
                        'merge' => 'D2:F2',
                        'cell' => 'D2',
                        'name' => '',
                    ],
                ],
                'otherData' => [
                    'imageFields' => ['col_b', 'col_d'],
                    'imageWidth' => 220,
                    'imageHeight' => 70,
                    'startRow' => 2,
                ],
            ],
        ];

        // Sheet 2+: 按设备分组，每设备一个Sheet
        $machineIdGroups = [];
        foreach ($details as $row) {
            if ((int)($row['plan_quantity'] ?? 0) <= 0) continue;
            $machineId = $row['machine_id'] ?? '';
            if ($machineId === '') continue;
            $machineIdGroups[$machineId][] = $row;
        }

        // 获取设备名称映射
        $machineNames = [];
        if ($machineIdGroups) {
            $machines = MachineModel::where([['machine_id', 'in', array_keys($machineIdGroups)]])
                ->field('machine_id,machine_name')
                ->select()->toArray();
            foreach ($machines as $m) {
                $machineNames[$m['machine_id']] = $m['machine_name'];
            }
        }

        $deviceTitle = [
            'col_a' => '商品名称',
            'col_b' => 'SKU',
            'col_c' => '领料数量',
            'col_d' => '创建人',
            'col_e' => '创建时间',
            'col_f' => '近30天销售额',
        ];

        foreach ($machineIdGroups as $machineId => $rows) {
            // 同设备同g_id跨货道合并，数量累加
            $goodsMap = [];
            foreach ($rows as $row) {
                $order = $orderMap[$row['order_id']] ?? [];
                $channel = $channelMap[$row['m_id'] . '_' . $row['mc_id']] ?? [];
                $gId = (int)($row['g_id'] ?? 0) ?: (int)($channel['g_id'] ?? 0);
                $goods = $exportGoodsMap[$gId] ?? $channel;
                $gIdKey = $gId ? (string)$gId : ($row['sku'] ?? '');
                // 查询近30天销售额，仅在首次遇到该g_id时查询
                if (!isset($goodsMap[$gIdKey])) {
                    $sales30Days = $gId ? $this->getSalesAmount($machineId, $gId) : 0;
                    $goodsMap[$gIdKey] = [
                        'g_name' => $goods['g_name'] ?? '',
                        'sku' => $row['sku'] ?? '',
                        'plan_quantity' => 0,
                        'creator_name' => $order['creator_name'] ?? '',
                        'created_at' => $order['created_at'] ?? '',
                        'sales_30_days' => $sales30Days,
                    ];
                }
                $goodsMap[$gIdKey]['plan_quantity'] += (int)($row['plan_quantity'] ?? 0);
                if (empty($goodsMap[$gIdKey]['g_name']) && !empty($goods['g_name'])) {
                    $goodsMap[$gIdKey]['g_name'] = $goods['g_name'];
                }
            }
            $deviceList = [];
            foreach ($goodsMap as $item) {
                $deviceList[] = [
                    'col_a' => $item['g_name'],
                    'col_b' => $item['sku'],
                    'col_c' => $item['plan_quantity'],
                    'col_d' => $item['creator_name'],
                    'col_e' => $item['created_at'],
                    'col_f' => $item['sales_30_days'] ?? 0,
                ];
            }
            $sheetName = $machineId;
            $sheets[] = [
                'sheetName' => $sheetName,
                'title' => $deviceTitle,
                'list' => $deviceList,
            ];
        }

        $result = $this->sendToExportMultiSheet('设备管理-预补货管理', $filename, $sheets);

        $state = 0;
        if (is_object($result) && method_exists($result, 'getData')) {
            $data = $result->getData();
            $state = (int)($data['state'] ?? 0);
        } else {
            $data = obj2arr($result);
            $state = (int)($data['state'] ?? 0);
        }
        if ($state == 200) {
            $now = date('Y-m-d H:i:s');
            PreReplenishmentOrderModel::where([['id', 'in', $orderIds]])
                ->update([
                    'export_status' => 1,
                    'export_time' => Db::raw("CASE WHEN export_time IS NULL THEN '{$now}' ELSE export_time END"),
                ]);
        }

        return $result;
    }

    /**
     * 查询补货明细视频地址
     * @param array $where
     * @return array|null
     */
    public function getReplenishmentDetailVideo($where)
    {
        $order = PreReplenishmentOrderModel::getFind(['id' => $where['order_id']], 'id');
        if (!$order) {
            return null;
        }
        return PreReplenishmentVideoModel::getFind(
            ['order_id' => $order['id'], 'machine_id' => $where['machine_id']],
            'id,replenishment_video',
            'id desc'
        );
    }

    public function reportLog($postData)
    {
        $recordNo = $postData['record_no'] ?? '';
        $machineId = $postData['machine_id'] ?? '';
        $channelCode = $postData['channel_code'] ?? '';
        $sku = $postData['sku'] ?? '';
        $quantity = $postData['quantity'] ?? null;

        if (!$recordNo || !$machineId || !$channelCode || $sku === '' || $quantity === null) {
            return returnState(4001, '参数错误');
        }

        $machine = MachineModel::getFind(['machine_id' => $machineId], 'm_id,machine_id');
        if (!$machine) {
            return returnState(4001, '参数错误: 设备不存在');
        }

        $reportTime = $postData['report_time'] ?? date('Y-m-d H:i:s');

        Db::startTrans();
        try {
            $result = $this->appendPreReplenishmentLogAndSync($recordNo, [
                'm_id' => $machine['m_id'],
                'machine_id' => $machineId,
                'channel_code' => $channelCode,
                'sku' => $sku,
                'quantity' => $quantity,
                'report_time' => $reportTime,
                'raw_payload' => arr2json($postData),
            ]);

            if (!$result) {
                Db::rollback();
                return returnState(5000, '系统错误');
            }

            Db::commit();
            return returnState(200, '上报成功', ['record_no' => $recordNo]);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1);
            return returnState(5000, '系统错误');
        }
    }

    /**
     * 手动完结预补货单
     * 未上报的明细按实际补货 0、少补处理，并将补货次数置为 1，防止设备再次上报。
     * @param $postData
     * @return array
     */
    public function finishOrder($postData)
    {
        $id = $postData['id'] ?? 0;
        if (!$id) {
            return returnState(4001, '参数错误: id不能为空');
        }

        Db::startTrans();
        try {
            $order = PreReplenishmentOrderModel::where(['id' => $id])
                ->lock(true)
                ->find();
            if (!$order) {
                Db::rollback();
                return returnState(4003, '单据不存在');
            }

            if ((int)$order['biz_status'] !== 1) {
                Db::rollback();
                return returnState(4004, '该预补货单已完结');
            }

            $affected = PreReplenishmentDetailModel::where(['order_id' => $id])
                ->whereNull('actual_quantity')
                ->update([
                    'actual_quantity' => 0,
                    'compare_status' => 3,
                    'order_count' => 1,
                ]);

            $orderUpdated = PreReplenishmentOrderModel::where(['id' => $id])
                ->update(['biz_status' => 3]);
            if (!$orderUpdated) {
                throw new \Exception('预补货单状态更新失败');
            }

            Db::commit();
            return returnState(200, '预补货单完结成功', ['affected' => $affected]);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1);
            return returnState(5000, '系统错误');
        }
    }

    /**
     * 重置补货次数（order_count 置 0）
     * @param $postData
     * @return array
     */
    public function resetReplenishmentCount($postData)
    {
        $orderId = $postData['order_id'] ?? 0;
        if (!$orderId) {
            return returnState(4001, '参数错误: order_id不能为空');
        }

        $order = PreReplenishmentOrderModel::getFind(['id' => $orderId], 'id,biz_status');
        if (!$order) {
            return returnState(4003, '单据不存在');
        }
        if ((int)$order['biz_status'] !== 1) {
            return returnState(4004, '已完结的预补货单不能重置补货次数');
        }

        $updated = PreReplenishmentDetailModel::where(['order_id' => $orderId])
            ->update(['order_count' => 0]);

        return returnState(200, '重置成功', ['affected' => $updated]);
    }

    /**
     * 删除补货单（仅允许删除未补货的单据）
     * @param $postData
     * @return array
     */
    public function deleteOrder($postData)
    {
        $id = $postData['id'] ?? 0;
        if (!$id) {
            return returnState(4001, '参数错误: id不能为空');
        }

        $order = PreReplenishmentOrderModel::getFind(['id' => $id], 'id,record_no,biz_status');
        if (!$order) {
            return returnState(4003, '单据不存在');
        }

        if ($order['biz_status'] != 1) {
            return returnState(4004, '只有未补货的单据才能删除');
        }

        Db::startTrans();
        try {
            PreReplenishmentDetailModel::whereDel(['order_id' => $id]);
            PreReplenishmentOrderModel::whereDel(['id' => $id]);
            Db::commit();
            return returnState(200, '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1);
            return returnState(5000, '系统错误');
        }
    }

    /**
     * 查询销售额（已扣除退款）
     * @param string $machineId
     * @param int $gId
     * @param int $days 统计天数，默认30
     * @param int $mcId 货道ID，传0则不过滤货道
     * @return float
     */
    private function getSalesAmount($machineId, $gId, $days = 30, $mcId = 0)
    {
        $daysAgo = strtotime("-{$days} days");
        $query = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where('so.machine_id', $machineId)
            ->where('sod.g_id', $gId)
            ->where('so.pay_status', 3)
            ->where('so.pay_time', '>=', $daysAgo);
        if ($mcId) {
            $query->where('sod.mc_id', $mcId);
        }
        $result = $query->field('COALESCE(SUM(sod.total_sod_price), 0) - COALESCE(SUM(sod.refund_amount), 0) AS sales_amount')
            ->find();
        return round((float)($result['sales_amount'] ?? 0), 2);
    }
}
