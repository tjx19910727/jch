<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 19:11
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Management\ManagementClient;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelSchemeTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Service\Machine\RecommendSchemeService;

class MachineSchemeClient extends ManagementClient
{
    use MachineChannelSchemeTrait;
    use MachineGoodsTrait;
    use MachineTrait;
    use GoodsTrait;
    use GoodsChangeTrait;

    /**
     * 根据布局模板和外部商品列表生成并保存推荐方案
     */
    public function getRecommendScheme()
    {
        $postData = input();
        $mlmId = intval($postData['mlm_id'] ?? 0);
        if (!$mlmId) {
            return $this->rFail("请提交mlm_id参数");
        }

        $result = $this->calculateRecommendSchemeData($postData);
        if (!$result['success']) {
            return $this->rFail($result['message']);
        }

        $saveResult = $this->saveRecommendScheme($mlmId, $result['data']);
        if (!$saveResult['success']) {
            return $this->rFail($saveResult['message']);
        }
        $result['data']['mcs_id'] = $saveResult['mcs_id'];
        return $this->r(200, "方案生成并保存成功", $result['data']);
    }

    /**
     * 导出推荐上架方案：1按货架，2按SKU，3按层级。
     */
    public function exportRecommendScheme()
    {
        $postData = input();
        $mcsId = intval($postData['mcs_id'] ?? 0);
        if (!$mcsId) {
            return $this->rFail("请提交mcs_id参数");
        }
        $type = intval($postData['type'] ?? 0);
        if (!in_array($type, [1, 2, 3], true)) {
            return $this->rFail("type参数错误");
        }

        $scheme = $this->getMachineChannelSchemeFind(
            ['mcs_id' => $mcsId],
            'mcs_id,mlm_id,scheme_name'
        );
        if (!$scheme) {
            return $this->rFail("方案不存在");
        }
        $detailsResult = $this->getMachineChannelSchemeDetailList(
            ['mcs_id' => $mcsId],
            0,
            '*',
            'mcsd_id asc'
        );
        $details = $detailsResult ? $detailsResult->toArray() : [];
        if (!$details) {
            return $this->rFail("方案明细为空");
        }

        if ($type === 1) {
            return $this->exportRecommendByShelf($details);
        }
        if ($type === 2) {
            return $this->exportRecommendBySku($details);
        }
        return $this->exportRecommendByShelfLevel(
            $details,
            intval($scheme['mlm_id'])
        );
    }

    /**
     * 统一计算推荐方案，确保预览和导出使用相同结果。
     */
    protected function calculateRecommendSchemeData($postData)
    {
        $mlmId = intval($postData['mlm_id'] ?? 0);
        $priorityType = 'amount';
        $goodsListInput = $postData['goods_list'] ?? ($postData['goods_lists'] ?? null);
        $goodsList = $this->normalizeRecommendGoodsLists($goodsListInput);

        if (!$mlmId) {
            return ['success' => false, 'message' => "请提交mlm_id参数"];
        }
        if ($goodsList === false) {
            return ['success' => false, 'message' => "goods_list格式错误"];
        }
        if (!$goodsList) {
            return ['success' => false, 'message' => "请提交goods_list参数"];
        }

        $service = new RecommendSchemeService();
        $goodsDetails = $service->getGoodsDetails($goodsList);
        if (!$goodsDetails) {
            return ['success' => false, 'message' => "商品不存在或ID无效"];
        }

        $schemeDetails = $service->calculate($mlmId, $goodsDetails, $priorityType);
        if ($schemeDetails === false) {
            return ['success' => false, 'message' => $service->getError()];
        }
        if (!$schemeDetails) {
            return ['success' => false, 'message' => "未生成可用推荐方案"];
        }

        $skippedGoods = $service->getSkippedGoods();
        $totalGoods = 0;
        $totalAmount = 0;
        $totalCost = 0;
        $skuSet = [];
        foreach ($schemeDetails as $d) {
            $totalGoods += intval($d['quantity']);
            $totalAmount += floatval($d['total_amount']);
            $totalCost += floatval($d['total_cost']);
            $skuSet[intval($d['g_id'])] = true;
        }
        $totalChannels = $service->getLayoutChannelCount();
        $assignedChannels = count($schemeDetails);

        $responseData = [
            'priority_type' => $priorityType,
            'scheme' => [
                'total_goods' => $totalGoods,
                'total_quantity' => $totalGoods,
                'total_amount' => round($totalAmount, 2),
                'total_cost' => round($totalCost, 2),
                'total_sku' => count($skuSet),
                'total_channels' => $totalChannels,
                'assigned_channels' => $assignedChannels,
                'unassigned_channels' => max(0, $totalChannels - $assignedChannels),
            ],
            'details' => $schemeDetails,
        ];

        if (!empty($skippedGoods)) {
            $responseData['warn_skipped_goods'] = $skippedGoods;
        }

        return ['success' => true, 'message' => '', 'data' => $responseData];
    }

    /**
     * 保存模板级推荐方案，不绑定具体设备。
     */
    protected function saveRecommendScheme($mlmId, $schemeData)
    {
        $now = time();
        $this->startTrans();
        try {
            $summary = $schemeData['scheme'];
            $saveData = [
                'machine_id' => '',
                'm_id' => 0,
                'mlm_id' => intval($mlmId),
                'scheme_name' => '推荐方案-' . date('YmdHis'),
                'priority_type' => 'amount',
                'total_goods' => intval($summary['total_goods']),
                'total_amount' => round(floatval($summary['total_amount']), 2),
                'total_sku' => intval($summary['total_sku']),
                'status' => 1,
                'create_time' => $now,
                'update_time' => $now,
            ];
            if (!empty($schemeData['warn_skipped_goods'])) {
                $saveData['skipped_goods'] = json_encode(
                    $schemeData['warn_skipped_goods'],
                    JSON_UNESCAPED_UNICODE
                );
            }
            $mcsId = $this->addMachineChannelScheme($saveData);
            if (!$mcsId) {
                throw new \RuntimeException("方案保存失败");
            }

            $insertAll = [];
            foreach ($schemeData['details'] as $detail) {
                $insertAll[] = [
                    'mcs_id' => $mcsId,
                    'mld_id' => intval($detail['mld_id']),
                    'channel_code' => $detail['channel_code'],
                    'g_id' => intval($detail['g_id']),
                    'g_name' => $detail['g_name'],
                    'sku' => $detail['sku'],
                    'retail_price' => floatval($detail['retail_price']),
                    'quantity' => intval($detail['quantity']),
                    'total_amount' => round(floatval($detail['total_amount']), 2),
                    'pos_x' => floatval($detail['pos_x']),
                    'pos_y' => floatval($detail['pos_y']),
                    'status' => 1,
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }
            $this->addMachineChannelSchemeDetailAll($insertAll);
            $this->commitTrans();
            return ['success' => true, 'message' => '', 'mcs_id' => $mcsId];
        } catch (\Throwable $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function exportRecommendByShelf($details)
    {
        $title = [
            'channel_code' => '槽位',
            'sku' => 'SKU',
            'g_name' => '商品名称',
            'quantity' => '推荐数量',
            'retail_price' => '售价',
            'total_amount' => '零售总额',
        ];
        return $this->sendToExport(
            '设备管理-推荐上架方案',
            '推荐方案-按货架-' . date('YmdHis'),
            $title,
            $details
        );
    }

    protected function exportRecommendBySku($details)
    {
        $rows = [];
        foreach ($details as $detail) {
            $key = intval($detail['g_id']);
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'sku' => $detail['sku'],
                    'g_name' => $detail['g_name'],
                    'channel_code' => [],
                    'channel_num' => 0,
                    'quantity' => 0,
                    'retail_price' => $detail['retail_price'],
                    'total_amount' => 0,
                ];
            }
            $rows[$key]['channel_code'][] = $detail['channel_code'];
            $rows[$key]['channel_num']++;
            $rows[$key]['quantity'] += intval($detail['quantity']);
            $rows[$key]['total_amount'] += floatval($detail['total_amount']);
        }
        foreach ($rows as &$row) {
            $row['channel_code'] = implode(',', $row['channel_code']);
            $row['total_amount'] = round($row['total_amount'], 2);
        }
        unset($row);

        $title = [
            'sku' => 'SKU',
            'g_name' => '商品名称',
            'channel_code' => '槽位',
            'channel_num' => '货道数量',
            'quantity' => '推荐总数量',
            'retail_price' => '售价',
            'total_amount' => '零售总额',
        ];
        return $this->sendToExport(
            '设备管理-推荐上架方案',
            '推荐方案-按SKU-' . date('YmdHis'),
            $title,
            array_values($rows)
        );
    }

    protected function exportRecommendByShelfLevel($details, $mlmId)
    {
        $levels = [];
        $specialChannels = [];
        foreach ($details as $detail) {
            $code = trim((string)($detail['channel_code'] ?? ''));
            if (preg_match('/^([a-zA-Z]+)(\d+)$/', $code, $matches)) {
                $level = strtoupper($matches[1]);
                $detail['_level_number'] = intval($matches[2]);
                $levels[$level][] = $detail;
            } else {
                $specialChannels[] = $detail;
            }
        }
        uksort($levels, 'strnatcasecmp');
        foreach ($levels as &$levelDetails) {
            usort($levelDetails, function ($a, $b) {
                $compare = intval($a['_level_number']) <=> intval($b['_level_number']);
                return $compare !== 0
                    ? $compare
                    : strnatcasecmp((string)$a['channel_code'], (string)$b['channel_code']);
            });
        }
        unset($levelDetails);
        usort($specialChannels, function ($a, $b) {
            return strnatcasecmp((string)$a['channel_code'], (string)$b['channel_code']);
        });

        $groups = array_values($levels);
        if ($specialChannels) {
            $groups[] = $specialChannels;
        }
        $maxChannelCount = 0;
        foreach ($groups as $group) {
            $maxChannelCount = max($maxChannelCount, count($group));
        }
        if ($maxChannelCount > 51) {
            return $this->rFail('单层货道数量不能超过51个');
        }

        $columnKeys = [];
        for ($index = 0; $index <= $maxChannelCount; $index++) {
            $columnKeys[] = 'column_' . $index;
        }
        $buildRow = function ($label, $field, $group) use ($columnKeys) {
            $row = array_fill_keys($columnKeys, '');
            $row['column_0'] = $label;
            foreach ($group as $index => $detail) {
                $row['column_' . ($index + 1)] = $field === 'channel_code'
                    ? (string)$detail['channel_code']
                    : (string)($detail[$field] ?? '');
            }
            return $row;
        };

        $fields = [
            ['商品名称', 'g_name'],
            ['商品SKU', 'sku'],
            ['推荐数量', 'quantity'],
            ['商品售价', 'retail_price'],
            ['零售总额', 'total_amount'],
        ];

        $firstGroup = array_shift($groups);
        $title = $buildRow('信息项', 'channel_code', $firstGroup);
        $list = [];
        $rowHeights = [3 => 25];
        $boldRows = [3];
        $excelRow = 4;
        $appendGroup = function ($group) use (&$list, &$rowHeights, &$excelRow, $fields, $buildRow) {
            foreach ($fields as $field) {
                $list[] = $buildRow($field[0], $field[1], $group);
                $rowHeights[$excelRow++] = 25;
            }
        };
        $appendGroup($firstGroup);
        foreach ($groups as $group) {
            $list[] = array_fill_keys($columnKeys, '');
            $rowHeights[$excelRow++] = 12;
            $list[] = $buildRow('信息项', 'channel_code', $group);
            $boldRows[] = $excelRow;
            $rowHeights[$excelRow++] = 25;
            $appendGroup($group);
        }

        $lastColumn = $this->getRecommendExportColumnName(count($columnKeys));
        return $this->sendToExport(
            '设备管理-推荐上架方案-按层级',
            '推荐方案-按层级-' . date('YmdHis'),
            $title,
            $list,
            [
                'startRow' => 3,
                'merge' => [[
                    'merge' => 'A1:' . $lastColumn . '1',
                    'cell' => 'A1',
                    'name' => '布局模板ID：' . $mlmId,
                ]],
                'columnWidth' => 24,
                'wrapText' => true,
                'vertical' => 'center',
                'rowHeights' => $rowHeights,
                'boldRows' => $boldRows,
                'fontSizeRows' => array_fill_keys($boldRows, 13),
            ]
        );
    }

    private function getRecommendExportColumnName($columnCount)
    {
        $name = '';
        while ($columnCount > 0) {
            $columnCount--;
            $name = chr(65 + ($columnCount % 26)) . $name;
            $columnCount = intval(floor($columnCount / 26));
        }
        return $name ?: 'A';
    }

    protected function normalizeRecommendGoodsLists($goodsLists)
    {
        if (is_string($goodsLists)) {
            if ($goodsLists === '') {
                return [];
            }
            $goodsLists = json_decode($goodsLists, true);
            if (!is_array($goodsLists)) {
                return false;
            }
        }
        if (!is_array($goodsLists)) {
            return false;
        }

        $gIds = [];
        foreach ($goodsLists as $gId) {
            if (is_array($gId) || is_object($gId) || intval($gId) <= 0) {
                return false;
            }
            $gIds[] = intval($gId);
        }
        return array_values(array_unique($gIds));
    }

    /**
     * 获取方案列表
     */
    public function getList($where = [], $pageNum = 0, $field = "*", $order = "mcs_id desc", $rQ = 1)
    {
        $postData = input();
        if (!$where && $postData) {
            $pageNum = $postData['pageNum'] ?? 0;
            if (!empty($postData['machine_id'])) {
                $where[] = ['machine_id', 'like', '%' . $postData['machine_id'] . '%'];
            }
            if (!empty($postData['status'])) {
                $where[] = ['status', '=', intval($postData['status'])];
            }
        }
        $data = $this->getMachineChannelSchemeList($where, $pageNum, $field, $order);
        return $this->rQ($data);
    }

    /**
     * 获取方案详情
     */
    public function getDetail()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");

        $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();

        // 解析被跳过的商品JSON
        $skippedGoods = [];
        if (!empty($scheme['skipped_goods'])) {
            $decoded = json_decode($scheme['skipped_goods'], true);
            if (is_array($decoded)) {
                $skippedGoods = $decoded;
            }
        }

        $result = [
            'scheme' => $scheme,
            'details' => $details,
        ];
        if (!empty($skippedGoods)) {
            $result['warn_skipped_goods'] = $skippedGoods;
        }

        return $this->r(200, "查询成功", $result);
    }

    /**
     * 确认方案 - 只保存方案状态，不执行真实上架。
     */
    public function confirmScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (intval($scheme['status']) !== 1) return $this->rFail("方案状态不允许确认");

        $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();
        if (!$details) return $this->rFail("方案明细为空");

        $this->startTrans();
        try {
            // 更新方案状态为已确认
            $this->updateMachineChannelScheme([
                'status' => 2,
                'update_time' => time(),
            ], ['mcs_id' => $mcsId]);

            // 更新所有明细状态为已确认
            $this->updateMachineChannelSchemeDetailStatus($mcsId, 2);

            $this->commitTrans();
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 方案真实上架 - 待确认方案可直接执行，已确认方案保持兼容。
     */
    public function applyScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (!in_array(intval($scheme['status']), [1, 2], true)) return $this->rFail("方案状态不允许真实上架");

        $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();
        if (!$details) return $this->rFail("方案明细为空");

        $mId = intval($scheme['m_id']);
        $machine = $this->getMachineFind(['m_id' => $mId], 'm_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->rFail("设备不存在");
        $machine = $machine->toArray();

        $missingChannels = [];
        $applyRows = [];
        foreach ($details as $d) {
            $channelCode = trim($d['channel_code']);
            $mc = $this->getMachineChannelFind([
                'm_id' => $mId,
                'channel_code' => $channelCode,
            ], 'mc_id,m_id,machine_id,channel_position,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,out_fail_stock,status');
            if (!$mc) {
                $missingChannels[] = $channelCode;
                continue;
            }
            $goods = $this->getGoodsFind(['g_id' => intval($d['g_id'])], 'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price');
            if (!$goods) {
                return $this->rFail("方案商品不存在：" . intval($d['g_id']));
            }
            $applyRows[] = [
                'detail' => $d,
                'mc' => $mc->toArray(),
                'goods' => $goods->toArray(),
            ];
        }
        if (!empty($missingChannels)) {
            return $this->rFail("方案货道不存在：" . implode(",", $missingChannels));
        }

        $this->startTrans();
        try {
            $sendList = [];
            foreach ($applyRows as $row) {
                $d = $row['detail'];
                $mc = $row['mc'];
                $goods = $row['goods'];
                $gId = intval($goods['g_id']);
                $oldGId = intval($mc['g_id'] ?? 0);
                $quantity = intval($d['quantity']);

                $baseChange = [
                    "m_id" => $machine['m_id'],
                    "machine_id" => $machine['machine_id'],
                    "machine_name" => $machine['machine_name'],
                    "mc_id" => $mc['mc_id'],
                    "channel_code" => $mc['channel_code'],
                    "mg_id" => $mc['mg_id'] ?? 0,
                    "g_id" => $mc['g_id'] ?? 0,
                    "g_name" => $mc['g_name'] ?? '',
                    "gc_id" => $mc['gc_id'] ?? 0,
                    "gc_name" => $mc['gc_name'] ?? '',
                    "pic" => $mc['pic'] ?? '',
                    "sku" => $mc['sku'] ?? '',
                    "bar_code" => $mc['bar_code'] ?? '',
                    "ao_id" => $machine['ao_id'],
                ];
                if ($oldGId > 0 && $oldGId !== $gId) {
                    $this->addGoodsChange(array_merge($baseChange, [
                        "change_value" => $mc['stock'] ?? 0,
                        "type" => 7,
                        "desc" => $this->lang("goodsChange.backstage_exchange_mc_under_old"),
                        "position" => 1,
                    ]));
                }

                $mgId = $this->getMachineGoodsValue(['m_id' => $mId, 'g_id' => $gId], 'mg_id') ?? 0;
                $this->updateMachineChannel([
                    'g_id' => $gId,
                    'mg_id' => $mgId,
                    'g_name' => $goods['g_name'] ?? '',
                    'gc_id' => $goods['gc_id'] ?? 0,
                    'gc_name' => $goods['gc_name'] ?? '',
                    'pic' => $goods['pic'] ?? '',
                    'sku' => $goods['sku'] ?? '',
                    'bar_code' => $goods['bar_code'] ?? '',
                    'cost_price' => $goods['cost_price'] ?? 0,
                    'market_price' => $goods['market_price'] ?? 0,
                    'retail_price' => floatval($d['retail_price']),
                    'stock' => $quantity,
                    'out_fail_stock' => 0,
                    'status' => 1,
                    'update_time' => time(),
                ], ['mc_id' => intval($mc['mc_id'])]);

                $this->addGoodsChange(array_merge($baseChange, [
                    "mg_id" => $mgId,
                    "g_id" => $gId,
                    "g_name" => $goods['g_name'] ?? '',
                    "gc_id" => $goods['gc_id'] ?? 0,
                    "gc_name" => $goods['gc_name'] ?? '',
                    "pic" => $goods['pic'] ?? '',
                    "sku" => $goods['sku'] ?? '',
                    "bar_code" => $goods['bar_code'] ?? '',
                    "change_value" => $quantity,
                    "type" => 6,
                    "desc" => $this->lang("goodsChange.backstage_exchange_mc_display_new"),
                    "position" => 1,
                ]));

                if (intval($mc['channel_position']) != 3) {
                    $sendList[] = [
                        'machine_id' => $mc['machine_id'],
                        'mc_id' => intval($mc['mc_id']),
                    ];
                }
            }

            // 标记为已上架，避免同一方案重复执行并重复写入库存变更记录。
            $this->updateMachineChannelScheme([
                'status' => 4,
                'update_time' => time(),
            ], ['mcs_id' => $mcsId]);
            $this->updateMachineChannelSchemeDetailStatus($mcsId, 4);

            $this->commitTrans();
            foreach ($sendList as $send) {
                $this->sendToMachine(['machine_id' => $send['machine_id']], 'updateMc', ['mc_id' => $send['mc_id']]);
            }
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 取消方案
     */
    public function cancelScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (intval($scheme['status']) !== 1) return $this->rFail("方案状态不允许取消");

        $this->updateMachineChannelScheme([
            'status' => 3,
            'update_time' => time(),
        ], ['mcs_id' => $mcsId]);

        $this->updateMachineChannelSchemeDetailStatus($mcsId, 3);

        return $this->rSuccess();
    }

    /**
     * 批量更新方案明细状态
     */
    protected function updateMachineChannelSchemeDetailStatus($mcsId, $status)
    {
        $model = new \app\AppFactory\Kernel\Model\Machine\MachineChannelSchemeDetailModel();
        return $model->where(['mcs_id' => intval($mcsId)])->update([
            'status' => intval($status),
            'update_time' => time(),
        ]);
    }
}
