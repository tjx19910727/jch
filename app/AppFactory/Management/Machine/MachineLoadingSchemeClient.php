<?php

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Service\Machine\LoadingSchemeSnapshotValidator;
use app\AppFactory\Kernel\Traits\Machine\MachineTemplateApiResponseTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineLoadingSchemeClient extends ManagementClient
{
    use MachineTemplateApiResponseTrait;

    const TEMPLATE_ACTIVE = 2;

    public function getGoodsList()
    {
        $postData = input();
        $page = max(1, intval($postData['page'] ?? 1));
        $pageSize = min(200, max(10, intval($postData['page_size'] ?? 100)));
        $keyword = trim((string)($postData['keyword'] ?? ''));
        $placementType = (string)($postData['placement_type'] ?? 'all');
        $gcId = intval($postData['gc_id'] ?? 0);
        if (!in_array($placementType, ['all', 'normal', 'hanging'], true)) {
            return $this->templateApiError(400, 'placement_type 只支持 all、normal、hanging');
        }

        try {
            $query = Db::name('goods')
                ->where('status', 1)
                ->where('width', '>', 0)
                ->where('height', '>', 0)
                ->where('length', '>', 0)
                ->whereIn('sell_channel', [1, 3]);
            $this->applyGoodsOrganizationScope($query);
            if ($gcId > 0) {
                $query->where('gc_id', $gcId);
            }
            if ($keyword !== '') {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->whereLike('g_name', '%' . $keyword . '%')
                        ->whereLike('sku', '%' . $keyword . '%', 'OR');
                });
            }

            $total = intval((clone $query)->count());
            $rows = $query
                ->field('g_id,g_name,sku,gc_name,gc_id,pic,placement_type,width,height,length')
                ->order('g_id desc')
                ->page($page, $pageSize)
                ->select();

            $list = [];
            foreach ($rows as $goods) {
                $list[] = [
                    'goods_id' => intval($goods['g_id']),
                    'sku' => (string)$goods['sku'],
                    'goods_name' => (string)$goods['g_name'],
                    'category_name' => (string)$goods['gc_name'],
                    'image_url' => $this->formatImageUrl($goods['pic'] ?? ''),
                    'width' => intval($goods['width']),
                    'height' => intval($goods['height']),
                    'depth' => intval($goods['length']),
                    'allow_rotation' => true,
                    'placement_type' => $this->mapPlacementType($goods['placement_type'] ?? 1),
                ];
            }

            return $this->templateApiSuccess([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '查询模拟商品失败');
        }
    }

    public function getDetail()
    {
        $templateId = intval(input('template_id'));
        if ($templateId <= 0) {
            return $this->templateApiError(400, 'template_id 参数错误');
        }

        try {
            if (!$this->findTemplate($templateId)) {
                return $this->templateApiError(404, '货道模板不存在');
            }
            $scheme = $this->findSchemeByTemplate($templateId);
            if (!$scheme) {
                return $this->templateApiSuccess(null);
            }
            $snapshot = json_decode((string)$scheme['snapshot_json'], true);
            if (!is_array($snapshot)) {
                return $this->templateApiError(500, '已保存方案快照解析失败');
            }

            $snapshot = $this->appendGoodsImages($snapshot);

            return $this->templateApiSuccess([
                'scheme_id' => intval($scheme['scheme_id']),
                'scheme_code' => (string)$scheme['scheme_code'],
                'template_id' => intval($scheme['template_id']),
                'template_version' => intval($scheme['template_version']),
                'scheme_name' => (string)$scheme['scheme_name'],
                'scheme_version' => intval($scheme['scheme_version']),
                'status' => 'saved',
                'saved_at' => intval($scheme['update_time']),
                'summary' => [
                    'total_capacity' => intval($scheme['total_capacity']),
                    'shelf_count' => intval($scheme['shelf_count']),
                    'front_row_count' => intval($scheme['front_row_count']),
                    'sku_count' => intval($scheme['sku_count']),
                    'width_utilization' => round(floatval($scheme['width_utilization']), 2),
                ],
                'snapshot' => $snapshot,
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '查询上货方案失败');
        }
    }

    /**
     * 按货架导出货道模板方案。
     */
    public function exportByShelf($templateId, $hasCostPriceAuth = true)
    {
        try {
            $result = $this->loadSchemeExportContext($templateId, $hasCostPriceAuth);
            if (!$result['valid']) {
                return $this->templateApiError($result['state'], $result['message']);
            }
            $context = $result['data'];

            $title = [
                'channel_code' => '槽位',
                'level_name' => '层级',
                'shelf_type_name' => '货架类型',
                'pic' => '商品图片',
                'sku' => 'SKU',
                'g_name' => '商品名称',
                'orientation_name' => '摆放方向',
                'depth_count' => '纵深数量',
                'layer_count' => '叠放数量',
                'capacity' => '最大数量',
                'retail_price' => '售价',
            ];
            if ($hasCostPriceAuth) {
                $title['cost_price'] = '成本价';
            }

            $list = [];
            $rowHeights = [4 => 25];
            $excelRow = 5;
            foreach ($context['channels'] as $channel) {
                $row = [];
                foreach ($title as $field => $_) {
                    $row[$field] = $channel[$field] ?? '';
                }
                $list[] = $row;
                $rowHeights[$excelRow++] = 80;
            }

            $list[] = array_fill_keys(array_keys($title), '');
            $rowHeights[$excelRow++] = 12;
            $summaryRows = $this->buildExportSummaryRows($context['summary'], $hasCostPriceAuth);
            $boldRows = [4];
            foreach ($summaryRows as $summaryRow) {
                $row = array_fill_keys(array_keys($title), '');
                $row['channel_code'] = $summaryRow['label'];
                $row['level_name'] = $summaryRow['value'];
                $list[] = $row;
                $boldRows[] = $excelRow;
                $rowHeights[$excelRow++] = 25;
            }

            $lastColumn = $this->getExportColumnName(count($title));
            return $this->sendToExport(
                '设备管理-货道模板-按货架导出',
                '货道模板-按货架-' . date('YmdHis'),
                $title,
                $list,
                [
                    'startRow' => 4,
                    'merge' => $this->buildExportHeaderMerges($context, $lastColumn),
                    'imageFields' => ['pic'],
                    'imageWidth' => 120,
                    'imageHeight' => 100,
                    'columnWidth' => 20,
                    'wrapText' => true,
                    'vertical' => 'center',
                    'rowHeights' => $rowHeights,
                    'boldRows' => $boldRows,
                ]
            );
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '导出货道模板失败');
        }
    }

    /**
     * 按层级导出货道模板方案。
     */
    public function exportByShelfLevel($templateId, $hasCostPriceAuth = true)
    {
        try {
            $result = $this->loadSchemeExportContext($templateId, $hasCostPriceAuth);
            if (!$result['valid']) {
                return $this->templateApiError($result['state'], $result['message']);
            }
            $context = $result['data'];
            $groups = $context['levels'];
            $maxChannelCount = 0;
            foreach ($groups as $group) {
                $maxChannelCount = max($maxChannelCount, count($group['channels']));
            }
            if ($maxChannelCount > 51) {
                return $this->templateApiError(422, '单层货道数量不能超过51个');
            }

            $columnKeys = [];
            for ($index = 0; $index <= $maxChannelCount; $index++) {
                $columnKeys[] = 'column_' . $index;
            }
            $buildHeaderRow = function (array $group) use ($columnKeys) {
                $row = array_fill_keys($columnKeys, '');
                $row['column_0'] = $group['level_name'] . '（' . $group['shelf_type_name'] . '）';
                foreach ($group['channels'] as $index => $channel) {
                    $row['column_' . ($index + 1)] = $channel['channel_code'];
                }
                return $row;
            };
            $buildDataRow = function ($label, $field, array $group) use ($columnKeys) {
                $row = array_fill_keys($columnKeys, '');
                $row['column_0'] = $label;
                foreach ($group['channels'] as $index => $channel) {
                    $row['column_' . ($index + 1)] = (string)($channel[$field] ?? '');
                }
                return $row;
            };

            $fields = [
                ['商品图片', 'pic'],
                ['商品名称', 'g_name'],
                ['商品SKU', 'sku'],
                ['摆放方向', 'orientation_name'],
                ['库存容量', 'capacity'],
                ['商品售价', 'retail_price'],
            ];
            if ($hasCostPriceAuth) {
                $fields[] = ['成本价', 'cost_price'];
            }

            $firstGroup = array_shift($groups);
            $title = $buildHeaderRow($firstGroup);
            $list = [];
            $rowHeights = [4 => 25];
            $boldRows = [4];
            $excelRow = 5;
            $appendGroup = function (array $group) use (
                &$list,
                &$rowHeights,
                &$excelRow,
                $fields,
                $buildDataRow
            ) {
                foreach ($fields as $field) {
                    $list[] = $buildDataRow($field[0], $field[1], $group);
                    $rowHeights[$excelRow++] = $field[1] === 'pic' ? 80 : 25;
                }
            };
            $appendGroup($firstGroup);

            foreach ($groups as $group) {
                $list[] = array_fill_keys($columnKeys, '');
                $rowHeights[$excelRow++] = 12;
                $list[] = $buildHeaderRow($group);
                $boldRows[] = $excelRow;
                $rowHeights[$excelRow++] = 25;
                $appendGroup($group);
            }

            $list[] = array_fill_keys($columnKeys, '');
            $rowHeights[$excelRow++] = 12;
            foreach ($this->buildExportSummaryRows($context['summary'], $hasCostPriceAuth) as $summaryRow) {
                $row = array_fill_keys($columnKeys, '');
                $row['column_0'] = $summaryRow['label'];
                $row['column_1'] = $summaryRow['value'];
                $list[] = $row;
                $boldRows[] = $excelRow;
                $rowHeights[$excelRow++] = 25;
            }

            $lastColumn = $this->getExportColumnName(count($columnKeys));
            return $this->sendToExport(
                '设备管理-货道模板-按层级导出',
                '货道模板-按层级-' . date('YmdHis'),
                $title,
                $list,
                [
                    'startRow' => 4,
                    'merge' => $this->buildExportHeaderMerges($context, $lastColumn),
                    'imageFields' => array_slice($columnKeys, 1),
                    'imageWidth' => 120,
                    'imageHeight' => 100,
                    'columnWidth' => 24,
                    'wrapText' => true,
                    'vertical' => 'center',
                    'rowHeights' => $rowHeights,
                    'boldRows' => $boldRows,
                    'fontSizeRows' => array_fill_keys($boldRows, 13),
                ]
            );
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '按层级导出货道模板失败');
        }
    }

    public function save()
    {
        $postData = input();
        $templateId = intval($postData['template_id'] ?? 0);
        if ($templateId <= 0) {
            return $this->templateApiError(400, 'template_id 参数错误');
        }

        $snapshot = $postData['snapshot'] ?? null;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }
        if (!is_array($snapshot)) {
            return $this->templateApiError(400, 'snapshot 必须为 JSON 对象');
        }

        Db::startTrans();
        try {
            $template = $this->findTemplate($templateId, true);
            if (!$template) {
                Db::rollback();
                return $this->templateApiError(404, '货道模板不存在');
            }

            $validator = new LoadingSchemeSnapshotValidator();
            $validation = $validator->validate($snapshot, $template);
            if (!$validation['valid']) {
                Db::rollback();
                return $this->templateApiError(
                    intval($validation['state'] ?? 422),
                    $validation['message']
                );
            }

            $goodsValidation = $this->validateSelectedGoods($validation['goods_ids']);
            if (!$goodsValidation['valid']) {
                Db::rollback();
                return $this->templateApiError(422, $goodsValidation['message']);
            }

            $currentScheme = $this->findSchemeByTemplate($templateId, true);

            $schemeName = trim((string)($postData['scheme_name'] ?? ''));
            if ($schemeName === '') {
                $schemeName = (string)$template['template_name'] . '上货方案';
            }
            if (mb_strlen($schemeName) > 100) {
                Db::rollback();
                return $this->templateApiError(400, 'scheme_name 最多 100 个字符');
            }

            $summary = $validation['summary'];
            $now = time();
            $managerId = intval($this->manager['manager_id'] ?? 0);
            $saveData = [
                'template_id' => $templateId,
                'ao_id' => intval($template['ao_id']),
                'template_version' => intval($template['template_version']),
                'scheme_name' => $schemeName,
                'schema_version' => 1,
                'snapshot_json' => $validation['snapshot_json'],
                'total_capacity' => intval($summary['total_capacity']),
                'shelf_count' => intval($summary['shelf_count']),
                'front_row_count' => intval($summary['front_row_count']),
                'sku_count' => intval($summary['sku_count']),
                'width_utilization' => round(floatval($summary['width_utilization']), 2),
                'update_id' => $managerId,
                'update_time' => $now,
            ];

            if ($currentScheme) {
                $newSchemeVersion = intval($currentScheme['scheme_version']) + 1;
                $saveData['scheme_version'] = $newSchemeVersion;
                Db::name('machine_loading_scheme')
                    ->where('scheme_id', $currentScheme['scheme_id'])
                    ->where('template_id', $templateId)
                    ->update($saveData);
                $schemeId = intval($currentScheme['scheme_id']);
                $schemeCode = (string)$currentScheme['scheme_code'];
            } else {
                $newSchemeVersion = 1;
                $schemeCode = $this->generateCode('PLN');
                $saveData += [
                    'scheme_code' => $schemeCode,
                    'scheme_version' => $newSchemeVersion,
                    'creator' => $managerId,
                    'create_time' => $now,
                ];
                $schemeId = intval(Db::name('machine_loading_scheme')->insertGetId($saveData));
            }

            Db::commit();
            return $this->templateApiSuccess([
                'scheme_id' => $schemeId,
                'scheme_code' => $schemeCode,
                'scheme_version' => $newSchemeVersion,
                'template_id' => $templateId,
                'template_version' => intval($template['template_version']),
                'saved_at' => $now,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->templateApiError(409, '模板已经存在上货方案，请刷新后重试');
            }
            return $this->templateApiError(500, '保存上货方案失败');
        }
    }

    /**
     * 读取方案快照并转换成可导出的虚拟货道。
     */
    protected function loadSchemeExportContext($templateId, $hasCostPriceAuth)
    {
        $templateId = intval($templateId);
        if ($templateId <= 0) {
            return $this->invalidExportContext(400, 'template_id 参数错误');
        }

        $template = $this->findTemplate($templateId);
        if (!$template) {
            return $this->invalidExportContext(404, '货道模板不存在');
        }
        $scheme = $this->findSchemeByTemplate($templateId);
        if (!$scheme) {
            return $this->invalidExportContext(404, '货道模板尚未保存上货方案');
        }

        $snapshot = json_decode((string)$scheme['snapshot_json'], true);
        $layout = is_array($snapshot) ? ($snapshot['layout'] ?? null) : null;
        $shelves = is_array($layout) ? ($layout['shelves'] ?? null) : null;
        $placements = is_array($layout) ? ($layout['placements'] ?? null) : null;
        if (!is_array($shelves) || !is_array($placements) || !$placements) {
            return $this->invalidExportContext(422, '上货方案快照缺少货架或货道数据');
        }

        $snapshotGoodsMap = [];
        foreach (($snapshot['goods_snapshots'] ?? []) as $goodsSnapshot) {
            if (!is_array($goodsSnapshot)) {
                continue;
            }
            $goodsId = intval($goodsSnapshot['goods_id'] ?? 0);
            if ($goodsId > 0) {
                $snapshotGoodsMap[$goodsId] = $goodsSnapshot;
            }
        }

        $goodsIds = [];
        $placementsByShelf = [];
        foreach ($placements as $placement) {
            if (!is_array($placement)) {
                continue;
            }
            $goodsId = intval($placement['goods_id'] ?? 0);
            $shelfIndex = intval($placement['shelf_index'] ?? -1);
            if ($goodsId <= 0 || $shelfIndex < 0) {
                return $this->invalidExportContext(422, '上货方案中存在无效货道数据');
            }
            $goodsIds[$goodsId] = $goodsId;
            $placementsByShelf[$shelfIndex][] = $placement;
        }

        $goodsQuery = Db::name('goods')->whereIn('g_id', array_values($goodsIds));
        $this->applyGoodsOrganizationScope($goodsQuery);
        $goodsFields = 'g_id,g_name,sku,pic,retail_price';
        if ($hasCostPriceAuth) {
            $goodsFields .= ',cost_price';
        }
        $goodsRows = $goodsQuery->field($goodsFields)->select();
        $currentGoodsMap = [];
        foreach ($goodsRows as $goods) {
            $currentGoodsMap[intval($goods['g_id'])] = $goods;
        }
        $missingGoodsIds = array_values(array_diff(array_values($goodsIds), array_keys($currentGoodsMap)));
        if ($missingGoodsIds) {
            return $this->invalidExportContext(
                422,
                '方案商品不存在或无权访问，无法计算导出金额：' . implode(',', $missingGoodsIds)
            );
        }

        usort($shelves, function ($left, $right) {
            $yCompare = floatval($right['y_start'] ?? 0) <=> floatval($left['y_start'] ?? 0);
            if ($yCompare !== 0) {
                return $yCompare;
            }
            return intval($right['shelf_index'] ?? 0) <=> intval($left['shelf_index'] ?? 0);
        });

        $channels = [];
        $levels = [];
        $placedGoodsIds = [];
        $totalGoodsCount = 0;
        $totalRetailCents = 0;
        $totalCostCents = 0;

        foreach ($shelves as $levelIndex => $shelf) {
            if (!is_array($shelf)) {
                continue;
            }
            $shelfIndex = intval($shelf['shelf_index'] ?? -1);
            $levelCode = $this->getExportColumnName($levelIndex + 1);
            $levelChannels = $placementsByShelf[$shelfIndex] ?? [];
            if (!$levelChannels) {
                continue;
            }
            usort($levelChannels, function ($left, $right) {
                $orderCompare = intval($left['shelf_order'] ?? 0) <=> intval($right['shelf_order'] ?? 0);
                if ($orderCompare !== 0) {
                    return $orderCompare;
                }
                return floatval($left['x_start'] ?? 0) <=> floatval($right['x_start'] ?? 0);
            });

            $exportChannels = [];
            foreach ($levelChannels as $channelIndex => $placement) {
                $goodsId = intval($placement['goods_id']);
                $snapshotGoods = $snapshotGoodsMap[$goodsId] ?? [];
                $currentGoods = $currentGoodsMap[$goodsId];
                $capacity = intval($placement['capacity'] ?? 0);
                $retailPrice = floatval($currentGoods['retail_price'] ?? 0);
                $costPrice = $hasCostPriceAuth ? floatval($currentGoods['cost_price'] ?? 0) : 0;
                $currentImage = $this->formatImageUrl($currentGoods['pic'] ?? '');
                $snapshotImage = $this->formatImageUrl(
                    $snapshotGoods['image_url'] ?? ($snapshotGoods['pic'] ?? '')
                );

                $channel = [
                    'channel_code' => $levelCode . ($channelIndex + 1),
                    'level_code' => $levelCode,
                    'level_name' => $levelCode . '层',
                    'shelf_type_name' => ($shelf['shelf_type'] ?? '') === 'hook' ? '挂钩层' : '普通层',
                    'goods_id' => $goodsId,
                    'pic' => $currentImage !== '' ? $currentImage : $snapshotImage,
                    'sku' => (string)($snapshotGoods['sku'] ?? ($currentGoods['sku'] ?? '')),
                    'g_name' => (string)($snapshotGoods['goods_name'] ?? ($currentGoods['g_name'] ?? '')),
                    'orientation_name' => ($placement['orientation'] ?? '') === 'rotated' ? '横转' : '正向',
                    'depth_count' => intval($placement['depth_count'] ?? 0),
                    'layer_count' => intval($placement['layer_count'] ?? 0),
                    'capacity' => $capacity,
                    'retail_price' => $this->formatExportMoney($retailPrice),
                    'cost_price' => $this->formatExportMoney($costPrice),
                ];
                $channels[] = $channel;
                $exportChannels[] = $channel;
                $placedGoodsIds[$goodsId] = true;
                $totalGoodsCount += $capacity;
                $totalRetailCents += $capacity * $this->moneyToCents($retailPrice);
                if ($hasCostPriceAuth) {
                    $totalCostCents += $capacity * $this->moneyToCents($costPrice);
                }
            }

            $levels[] = [
                'level_code' => $levelCode,
                'level_name' => $levelCode . '层',
                'shelf_type_name' => ($shelf['shelf_type'] ?? '') === 'hook' ? '挂钩层' : '普通层',
                'channels' => $exportChannels,
            ];
        }

        if (!$channels) {
            return $this->invalidExportContext(422, '上货方案没有可导出的货道');
        }

        return [
            'valid' => true,
            'state' => 200,
            'message' => '',
            'data' => [
                'template' => $template,
                'scheme' => $scheme,
                'channels' => $channels,
                'levels' => $levels,
                'summary' => [
                    'sku_count' => count($placedGoodsIds),
                    'total_goods_count' => $totalGoodsCount,
                    'total_retail_amount' => $this->centsToMoney($totalRetailCents),
                    'total_cost_amount' => $this->centsToMoney($totalCostCents),
                ],
            ],
        ];
    }

    protected function invalidExportContext($state, $message)
    {
        return [
            'valid' => false,
            'state' => intval($state),
            'message' => (string)$message,
            'data' => null,
        ];
    }

    protected function buildExportSummaryRows(array $summary, $hasCostPriceAuth)
    {
        $rows = [
            ['label' => '机SKU数量', 'value' => intval($summary['sku_count'] ?? 0)],
            ['label' => '总商品数', 'value' => intval($summary['total_goods_count'] ?? 0)],
            ['label' => '总零售价', 'value' => (string)($summary['total_retail_amount'] ?? '0.00')],
        ];
        if ($hasCostPriceAuth) {
            $rows[] = ['label' => '总成本价', 'value' => (string)($summary['total_cost_amount'] ?? '0.00')];
        }
        return $rows;
    }

    protected function buildExportHeaderMerges(array $context, $lastColumn)
    {
        return [
            [
                'merge' => 'A1:' . $lastColumn . '1',
                'cell' => 'A1',
                'name' => '模板名称：' . (string)$context['template']['template_name'],
            ],
            [
                'merge' => 'A2:' . $lastColumn . '2',
                'cell' => 'A2',
                'name' => '方案名称：' . (string)$context['scheme']['scheme_name'],
            ],
        ];
    }

    protected function moneyToCents($money)
    {
        return intval(round(floatval($money) * 100));
    }

    protected function centsToMoney($cents)
    {
        return number_format(intval($cents) / 100, 2, '.', '');
    }

    protected function formatExportMoney($money)
    {
        return number_format(floatval($money), 2, '.', '');
    }

    protected function getExportColumnName($columnCount)
    {
        $name = '';
        while ($columnCount > 0) {
            $columnCount--;
            $name = chr(65 + ($columnCount % 26)) . $name;
            $columnCount = intdiv($columnCount, 26);
        }
        return $name ?: 'A';
    }

    protected function findTemplate($templateId, $lock = false)
    {
        $query = Db::name('machine_channel_template')
            ->where('template_id', intval($templateId))
            ->where('is_del', self::TEMPLATE_ACTIVE);
        $this->applyTemplateOrganizationScope($query);
        if ($lock) {
            $query->lock(true);
        }
        return $query->find();
    }

    protected function findSchemeByTemplate($templateId, $lock = false)
    {
        $query = Db::name('machine_loading_scheme')
            ->where('template_id', intval($templateId));
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where('ao_id', $aoId);
        }
        if ($lock) {
            $query->lock(true);
        }
        return $query->find();
    }

    protected function validateSelectedGoods(array $goodsIds)
    {
        $goodsIds = array_values(array_unique(array_map('intval', $goodsIds)));
        if (!$goodsIds) {
            return ['valid' => false, 'message' => '方案未选择商品'];
        }
        $query = Db::name('goods')
            ->whereIn('g_id', $goodsIds)
            ->where('status', 1)
            ->where('width', '>', 0)
            ->where('height', '>', 0)
            ->where('length', '>', 0)
            ->whereIn('sell_channel', [1, 3]);
        $this->applyGoodsOrganizationScope($query);
        $validIds = $query->column('g_id');
        $validIds = array_map('intval', $validIds ?: []);
        $missing = array_values(array_diff($goodsIds, $validIds));
        if ($missing) {
            return [
                'valid' => false,
                'message' => '方案引用了不存在、已禁用、尺寸不完整或无权访问的商品：' . implode(',', $missing),
            ];
        }
        return ['valid' => true, 'message' => ''];
    }

    protected function applyTemplateOrganizationScope($query)
    {
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where('ao_id', $aoId);
        }
        return $query;
    }

    protected function applyGoodsOrganizationScope($query)
    {
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where('ao_id', $aoId);
        }
        return $query;
    }

    protected function currentAoId()
    {
        return intval($this->manager['ao_id'] ?? 0);
    }

    protected function generateCode($prefix)
    {
        return $prefix . '-' . strtolower(base_convert((string)time(), 10, 36)) . '-' . bin2hex(random_bytes(5));
    }

    protected function formatImageUrl($pic)
    {
        $pic = trim((string)($pic ?? ''));
        if ($pic === '') {
            return '';
        }
        if (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0) {
            return $pic;
        }
        $host = rtrim((string)($this->host ?? ''), '/');
        if ($host === '') {
            return $pic;
        }
        return $host . '/' . ltrim($pic, '/');
    }

    protected function appendGoodsImages(array $snapshot)
    {
        if (empty($snapshot['goods_snapshots']) || !is_array($snapshot['goods_snapshots'])) {
            return $snapshot;
        }

        $goodsIds = [];
        foreach ($snapshot['goods_snapshots'] as $goodsSnapshot) {
            if (!is_array($goodsSnapshot)) {
                continue;
            }
            $goodsId = intval($goodsSnapshot['goods_id'] ?? 0);
            if ($goodsId > 0) {
                $goodsIds[$goodsId] = $goodsId;
            }
        }

        $imageMap = [];
        if ($goodsIds) {
            $query = Db::name('goods')
                ->whereIn('g_id', array_values($goodsIds));
            $this->applyGoodsOrganizationScope($query);
            $rows = $query->field('g_id,pic')->select();
            foreach ($rows as $goods) {
                $imageMap[intval($goods['g_id'])] = $this->formatImageUrl($goods['pic'] ?? '');
            }
        }

        foreach ($snapshot['goods_snapshots'] as &$goodsSnapshot) {
            if (!is_array($goodsSnapshot)) {
                continue;
            }
            $goodsId = intval($goodsSnapshot['goods_id'] ?? 0);
            $snapshotImage = $goodsSnapshot['image_url'] ?? ($goodsSnapshot['pic'] ?? '');
            $currentImage = $imageMap[$goodsId] ?? '';
            $goodsSnapshot['image_url'] = $currentImage !== ''
                ? $currentImage
                : $this->formatImageUrl($snapshotImage);
        }
        unset($goodsSnapshot);

        return $snapshot;
    }

    protected function mapPlacementType($placementType)
    {
        return intval($placementType) === 2 ? 'hanging' : 'normal';
    }
}
