<?php

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentDetailModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentLogModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentOrderModel;
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

        $machineList = MachineModel::where([['machine_id', 'in', $machineIds]])
            ->field('m_id,machine_id,machine_name')
            ->select()
            ->toArray();

        if (!$machineList) {
            return returnState(200, 'ok', ['machine_list' => []]);
        }

        $mIds = array_column($machineList, 'm_id');
        $channelList = MachineChannelModel::where([['m_id', 'in', $mIds]])
            ->field('m_id,mc_id,channel_code,stock,capacity,sku,g_name,pic')
            ->order('mc_id asc')
            ->select()
            ->toArray();

        $channelMap = [];
        foreach ($channelList as $channel) {
            $availableStock = $channel['capacity'] - $channel['stock'];
            if ($availableStock < 0) {
                $availableStock = 0;
            }
            $channelMap[$channel['m_id']][] = [
                'mc_id' => $channel['mc_id'],
                'channel_code' => $channel['channel_code'],
                'sku' => $channel['sku'],
                'g_name' => $channel['g_name'],
                'image_url' => $channel['pic'],
                'before_stock' => $channel['stock'],
                'capacity' => $channel['capacity'],
                'available_stock' => $availableStock,
            ];
        }

        $result = [];
        foreach ($machineList as $machine) {
            $result[] = [
                'machine_id' => $machine['machine_id'],
                'machine_name' => $machine['machine_name'],
                'channels' => $channelMap[$machine['m_id']] ?? [],
            ];
        }

        return returnState(200, 'ok', ['machine_list' => $result]);
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

        $order = PreReplenishmentOrderModel::getFind(['id' => $id], 'id,record_no');
        if (!$order) {
            return returnState(4003, '单据不存在');
        }

        $logCount = PreReplenishmentLogModel::getCount(['record_no' => $order['record_no']]);
        if ($logCount > 0) {
            return returnState(4004, '该补货单已进行补货，不允许修改');
        }

        $details = $this->normalizeDetails($postData['details'] ?? []);
        if (!$details) {
            return returnState(4001, '参数错误: 明细不能为空');
        }

        $checkResult = $this->buildOrderDetails($details);
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

    public function getOrderList($postData)
    {
        $page = $postData['page'] ?? 1;
        $pageSize = $postData['page_size'] ?? 20;

        $where = [];
        // 只允许查看当前 ao_id 下的数据
        $where[] = ['ao_id', '=', $this->manager['ao_id'] ?? 0];
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
            if ($row['sku']) {
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

        $details = PreReplenishmentDetailModel::where(['order_id' => $id])->order('id asc')->select()->toArray();
        $logs = PreReplenishmentLogModel::where(['record_no' => $order['record_no']])->order('id asc')->select()->toArray();

        // 查询设备名称映射
        $machineIds = array_values(array_unique(array_column($details, 'machine_id')));
        $machineNameMap = [];
        if ($machineIds) {
            $machines = MachineModel::where([['machine_id', 'in', $machineIds]])
                ->field('machine_id,machine_name')
                ->select()
                ->toArray();
            foreach ($machines as $m) {
                $machineNameMap[$m['machine_id']] = $m['machine_name'];
            }
        }

        // 查询货道商品信息映射（用于 pick_summary）
        $mIds = array_values(array_unique(array_column($details, 'm_id')));
        $mcIds = array_values(array_unique(array_column($details, 'mc_id')));
        $channelGoodsMap = [];
        if ($mIds && $mcIds) {
            $channels = MachineChannelModel::where([
                ['m_id', 'in', $mIds],
                ['mc_id', 'in', $mcIds],
            ])->field('m_id,mc_id,sku,g_name,pic')
                ->select()
                ->toArray();
            foreach ($channels as $ch) {
                $channelGoodsMap[$ch['m_id'] . '_' . $ch['mc_id']] = $ch;
            }
        }

        $pickSummaryMap = [];
        $deviceProgressMap = [];
        $planTotal = 0;
        $actualTotal = 0;

        foreach ($details as $detail) {
            $pickKey = $detail['sku'];
            if (!isset($pickSummaryMap[$pickKey])) {
                $channelKey = $detail['m_id'] . '_' . $detail['mc_id'];
                $goods = $channelGoodsMap[$channelKey] ?? [];
                $pickSummaryMap[$pickKey] = [
                    'sku' => $detail['sku'],
                    'need_quantity' => 0,
                    'g_name' => $goods['g_name'] ?? '',
                    'pic' => $goods['pic'] ?? '',
                ];
            }
            $pickSummaryMap[$pickKey]['need_quantity'] += $detail['plan_quantity'];

            if (!isset($deviceProgressMap[$detail['machine_id']])) {
                $deviceProgressMap[$detail['machine_id']] = [
                    'machine_id' => $detail['machine_id'],
                    'machine_name' => $machineNameMap[$detail['machine_id']] ?? '',
                    'biz_status' => 1,
                    'plan_total' => 0,
                    'actual_total' => 0,
                    'all_matched' => true,
                    'has_less' => false,
                    'has_more' => false,
                ];
            }

            $deviceProgressMap[$detail['machine_id']]['plan_total'] += $detail['plan_quantity'];
            $deviceProgressMap[$detail['machine_id']]['actual_total'] += ($detail['actual_quantity'] ?? 0);

            $compareStatus = (int)($detail['compare_status'] ?? 1);
            if ($compareStatus !== 2) {
                $deviceProgressMap[$detail['machine_id']]['all_matched'] = false;
            }
            if ($compareStatus === 3) {
                $deviceProgressMap[$detail['machine_id']]['has_less'] = true;
            }
            if (($detail['actual_quantity'] ?? null) !== null && $detail['actual_quantity'] > $detail['plan_quantity']) {
                $deviceProgressMap[$detail['machine_id']]['has_more'] = true;
            }

            $planTotal += $detail['plan_quantity'];
            $actualTotal += ($detail['actual_quantity'] ?? 0);
        }

        $deviceProgress = [];
        foreach ($deviceProgressMap as $item) {
            if ($item['has_more']) {
                $item['biz_status'] = 4;
            } elseif ($item['has_less']) {
                $item['biz_status'] = 3;
            } elseif ($item['all_matched'] && $item['plan_total'] > 0) {
                $item['biz_status'] = 2;
            } else {
                $item['biz_status'] = 1;
            }
            unset($item['all_matched'], $item['has_less'], $item['has_more']);
            $deviceProgress[] = $item;
        }

        $logsGroup = [];
        foreach ($logs as $log) {
            if (!isset($logsGroup[$log['machine_id']])) {
                $logsGroup[$log['machine_id']] = [
                    'm_id' => $log['m_id'],
                    'machine_id' => $log['machine_id'],
                    'log_count' => 0,
                    'actual_total' => 0,
                    'logs' => [],
                ];
            }
            $logsGroup[$log['machine_id']]['log_count'] += 1;
            $logsGroup[$log['machine_id']]['actual_total'] += $log['quantity'];
            $logsGroup[$log['machine_id']]['logs'][] = [
                'id' => $log['id'],
                'm_id' => $log['m_id'],
                'channel_code' => $log['channel_code'],
                'sku' => $log['sku'],
                'quantity' => $log['quantity'],
                'report_time' => $log['report_time'],
            ];
        }

        $summary = [
            'machine_count' => count($deviceProgress),
            'sku_count' => count($pickSummaryMap),
            'plan_total' => $planTotal,
            'actual_total' => $actualTotal,
        ];

        return returnState(200, 'ok', [
            'base_info' => [
                'id' => $order['id'],
                'record_no' => $order['record_no'],
                'biz_status' => $order['biz_status'],
                'export_status' => $order['export_status'],
                'creator_name' => $order['creator_name'],
                'remark' => $order['remark'],
                'created_at' => $order['created_at'],
                'export_time' => $order['export_time'],
            ],
            'summary' => $summary,
            'pick_summary' => array_values($pickSummaryMap),
            'device_progress' => array_values($deviceProgress),
            'details' => $details,
            'logs_group_by_machine' => array_values($logsGroup),
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
            ->field('id,order_id,m_id,machine_id,mc_id,channel_code,sku,plan_quantity,actual_quantity,compare_status')
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
            ])->field('m_id,mc_id,g_name,pic')->select()->toArray();
            foreach ($channels as $channel) {
                $channelMap[$channel['m_id'] . '_' . $channel['mc_id']] = $channel;
            }
        }

        $groupRows = [];
        foreach ($details as $row) {
            $order = $orderMap[$row['order_id']] ?? [];
            $channel = $channelMap[$row['m_id'] . '_' . $row['mc_id']] ?? [];
            $groupKey = ($order['record_no'] ?? '') . '|' . ($row['sku'] ?? '');
            if (!isset($groupRows[$groupKey])) {
                $recordNo = trim((string)($order['record_no'] ?? ''));
                $groupRows[$groupKey] = [
                    'record_no' => $order['record_no'] ?? '',
                    'order_bar_code_image' => $recordNo === '' ? '' : ('https://bwipjs-api.metafloor.com/?bcid=code128&includetext=true&scale=2&text=' . urlencode($recordNo)),
                    'goods_name' => $channel['g_name'] ?? '',
                    'goods_pic' => $channel['pic'] ?? '',
                    'sku' => $row['sku'] ?? '',
                    'plan_quantity' => 0,
                    'actual_quantity' => 0,
                    'actual_has_value' => false,
                    'creator_name' => $order['creator_name'] ?? '',
                    'created_at' => $order['created_at'] ?? '',
                ];
            }

            if ($groupRows[$groupKey]['goods_name'] === '' && !empty($channel['g_name'])) {
                $groupRows[$groupKey]['goods_name'] = $channel['g_name'];
            }
            if ($groupRows[$groupKey]['goods_pic'] === '' && !empty($channel['pic'])) {
                $groupRows[$groupKey]['goods_pic'] = $channel['pic'];
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

        $result = $this->sendToExport('设备管理-预补货管理', $filename, $title, $list, [
            'imageFields' => ['col_b', 'col_d'],
            'imageWidth' => 220,
            'imageHeight' => 70,
            'startRow' => 2,
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
        ]);

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
        return PreReplenishmentDetailModel::getFind(
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

        $updated = PreReplenishmentDetailModel::where(['order_id' => $orderId])
            ->update(['order_count' => 0]);

        return returnState(200, '重置成功', ['affected' => $updated]);
    }
}
