<?php

namespace app\AppFactory\Kernel\Service\Machine;

class LoadingSchemeSnapshotValidator
{
    const MAX_SNAPSHOT_BYTES = 1048576;
    const MAX_SELECTIONS = 100;
    const MAX_PLACEMENTS = 240;

    public function validate(array $snapshot, array $template)
    {
        if (intval($snapshot['schema_version'] ?? 0) !== 1) {
            return $this->fail('snapshot.schema_version 当前只支持 1');
        }

        foreach (['channel', 'goods_snapshots', 'selections', 'layout'] as $field) {
            if (!array_key_exists($field, $snapshot) || !is_array($snapshot[$field])) {
                return $this->fail('snapshot.' . $field . ' 结构不完整');
            }
        }

        $encoded = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return $this->fail('snapshot 不是有效的 JSON 数据');
        }
        if (strlen($encoded) > self::MAX_SNAPSHOT_BYTES) {
            return $this->fail('snapshot 不能超过 1 MB');
        }

        $channelResult = $this->validateChannel($snapshot['channel'], $template);
        if (!$channelResult['valid']) {
            return $channelResult;
        }

        if (count($snapshot['selections']) > self::MAX_SELECTIONS) {
            return $this->fail('selections 不能超过 ' . self::MAX_SELECTIONS . ' 条');
        }
        if (count($snapshot['goods_snapshots']) > self::MAX_SELECTIONS) {
            return $this->fail('goods_snapshots 不能超过 ' . self::MAX_SELECTIONS . ' 条');
        }

        $layout = $snapshot['layout'];
        foreach (['placements', 'shelves', 'summaries', 'unplaced', 'stats', 'explanations'] as $field) {
            if (!array_key_exists($field, $layout) || !is_array($layout[$field])) {
                return $this->fail('snapshot.layout.' . $field . ' 结构不完整');
            }
        }
        if (!$layout['placements']) {
            return $this->fail('至少需要一条 placement 才能保存方案');
        }
        if (count($layout['placements']) > self::MAX_PLACEMENTS) {
            return $this->fail('placements 不能超过 ' . self::MAX_PLACEMENTS . ' 条');
        }

        $goodsSnapshotIds = [];
        foreach ($snapshot['goods_snapshots'] as $index => $goods) {
            if (!is_array($goods)) {
                return $this->fail('goods_snapshots[' . $index . '] 格式错误');
            }
            $goodsId = intval($goods['goods_id'] ?? 0);
            if ($goodsId <= 0 || isset($goodsSnapshotIds[$goodsId])) {
                return $this->fail('goods_snapshots 商品 ID 无效或重复');
            }
            foreach (['width', 'height', 'depth'] as $sizeField) {
                if (!$this->isPositiveInteger($goods[$sizeField] ?? null)) {
                    return $this->fail('goods_snapshots[' . $index . '].' . $sizeField . ' 必须为正整数');
                }
            }
            $goodsSnapshotIds[$goodsId] = true;
        }

        $selectionIds = [];
        $selectionOrientations = [];
        foreach ($snapshot['selections'] as $index => $selection) {
            if (!is_array($selection)) {
                return $this->fail('selections[' . $index . '] 格式错误');
            }
            $goodsId = intval($selection['goods_id'] ?? 0);
            if ($goodsId <= 0 || isset($selectionIds[$goodsId])) {
                return $this->fail('selections 商品 ID 无效或重复');
            }
            if (!$this->validOrientation($selection['orientation'] ?? '')) {
                return $this->fail('selections[' . $index . '].orientation 取值错误');
            }
            if (!isset($goodsSnapshotIds[$goodsId])) {
                return $this->fail('selections 引用了缺少快照的商品：' . $goodsId);
            }
            $selectionIds[$goodsId] = true;
            $selectionOrientations[$goodsId] = $selection['orientation'];
        }

        $shelfIds = [];
        foreach ($layout['shelves'] as $index => $shelf) {
            if (!is_array($shelf) || !$this->isNonNegativeInteger($shelf['shelf_index'] ?? null)) {
                return $this->fail('shelves[' . $index . '].shelf_index 格式错误');
            }
            $shelfIndex = intval($shelf['shelf_index']);
            if (isset($shelfIds[$shelfIndex])) {
                return $this->fail('shelf_index 不能重复：' . $shelfIndex);
            }
            if (!in_array($shelf['shelf_type'] ?? '', ['shelf', 'hook'], true)) {
                return $this->fail('shelves[' . $index . '].shelf_type 取值错误');
            }
            foreach (['height', 'available_width'] as $field) {
                if (!$this->isPositiveNumber($shelf[$field] ?? null)) {
                    return $this->fail('shelves[' . $index . '].' . $field . ' 必须大于 0');
                }
            }
            foreach (['y_start', 'used_width', 'remaining_width', 'front_row_count', 'total_capacity', 'width_utilization'] as $field) {
                if (!$this->isNonNegativeNumber($shelf[$field] ?? null)) {
                    return $this->fail('shelves[' . $index . '].' . $field . ' 不能小于 0');
                }
            }
            if (floatval($shelf['width_utilization']) > 100) {
                return $this->fail('shelves[' . $index . '].width_utilization 不能大于 100');
            }
            $shelfIds[$shelfIndex] = true;
        }
        if (!$shelfIds) {
            return $this->fail('shelves 不能为空');
        }

        $placementIds = [];
        $orders = [];
        $shelfOrders = [];
        $ordersByShelf = [];
        $placedGoodsIds = [];
        $totalCapacity = 0;
        foreach ($layout['placements'] as $index => $placement) {
            if (!is_array($placement)) {
                return $this->fail('placements[' . $index . '] 格式错误');
            }
            $placementId = trim((string)($placement['placement_id'] ?? ''));
            if ($placementId === '' || strlen($placementId) > 100 || isset($placementIds[$placementId])) {
                return $this->fail('placement_id 不能为空、超长或重复');
            }
            $goodsId = intval($placement['goods_id'] ?? 0);
            if (!isset($selectionIds[$goodsId]) || !isset($goodsSnapshotIds[$goodsId])) {
                return $this->fail('placement 引用了未选择的商品：' . $goodsId);
            }
            if (!$this->validOrientation($placement['orientation'] ?? '')) {
                return $this->fail('placements[' . $index . '].orientation 取值错误');
            }
            if ($selectionOrientations[$goodsId] !== $placement['orientation']) {
                return $this->fail('placement 与 selections 中的商品朝向不一致：' . $goodsId);
            }
            if (!in_array($placement['placement_type'] ?? '', ['normal', 'hanging'], true)) {
                return $this->fail('placements[' . $index . '].placement_type 取值错误');
            }
            foreach (['order', 'shelf_index', 'shelf_order'] as $field) {
                if (!$this->isNonNegativeInteger($placement[$field] ?? null)) {
                    return $this->fail('placements[' . $index . '].' . $field . ' 必须为非负整数');
                }
            }
            foreach (['width', 'height', 'depth', 'slot_width', 'depth_count', 'layer_count', 'capacity'] as $field) {
                if (!$this->isPositiveNumber($placement[$field] ?? null)) {
                    return $this->fail('placements[' . $index . '].' . $field . ' 必须大于 0');
                }
            }
            foreach (['x_start', 'x_center', 'used_depth', 'remaining_depth'] as $field) {
                if (!$this->isNonNegativeNumber($placement[$field] ?? null)) {
                    return $this->fail('placements[' . $index . '].' . $field . ' 不能小于 0');
                }
            }

            $order = intval($placement['order']);
            $shelfIndex = intval($placement['shelf_index']);
            $shelfOrder = intval($placement['shelf_order']);
            if (!isset($shelfIds[$shelfIndex])) {
                return $this->fail('placement 引用了不存在的 shelf_index：' . $shelfIndex);
            }
            if (isset($orders[$order])) {
                return $this->fail('placement.order 不能重复：' . $order);
            }
            $shelfOrderKey = $shelfIndex . ':' . $shelfOrder;
            if (isset($shelfOrders[$shelfOrderKey])) {
                return $this->fail('同一层的 shelf_order 不能重复：' . $shelfOrderKey);
            }

            $placementIds[$placementId] = true;
            $orders[$order] = true;
            $shelfOrders[$shelfOrderKey] = true;
            $ordersByShelf[$shelfIndex][] = $shelfOrder;
            $placedGoodsIds[$goodsId] = true;
            $totalCapacity += intval($placement['capacity']);
        }

        $globalOrders = array_keys($orders);
        sort($globalOrders);
        if ($globalOrders !== range(0, count($globalOrders) - 1)) {
            return $this->fail('placement.order 必须从 0 开始连续递增');
        }
        foreach ($ordersByShelf as $shelfIndex => $shelfOrderList) {
            sort($shelfOrderList);
            if ($shelfOrderList !== range(0, count($shelfOrderList) - 1)) {
                return $this->fail('第 ' . $shelfIndex . ' 层的 shelf_order 必须从 0 开始连续递增');
            }
        }

        $summaryGoods = [];
        foreach ($layout['summaries'] as $index => $summary) {
            if (!is_array($summary)) {
                return $this->fail('summaries[' . $index . '] 格式错误');
            }
            $goodsId = intval($summary['goods_id'] ?? 0);
            if (!isset($selectionIds[$goodsId])) {
                return $this->fail('summary 引用了未选择的商品：' . $goodsId);
            }
            if (!$this->validOrientation($summary['orientation'] ?? '')) {
                return $this->fail('summaries[' . $index . '].orientation 取值错误');
            }
            $orientedSize = $summary['oriented_size'] ?? null;
            if (!is_array($orientedSize)) {
                return $this->fail('summaries[' . $index . '].oriented_size 格式错误');
            }
            foreach (['width', 'height', 'depth'] as $field) {
                if (!$this->isPositiveNumber($orientedSize[$field] ?? null)) {
                    return $this->fail('summaries[' . $index . '].oriented_size.' . $field . ' 必须大于 0');
                }
            }
            foreach (['facings', 'units_per_facing', 'depth_count', 'layer_count', 'total_capacity'] as $field) {
                if (!$this->isNonNegativeInteger($summary[$field] ?? null)) {
                    return $this->fail('summaries[' . $index . '].' . $field . ' 必须为非负整数');
                }
            }
            if (!is_string($summary['fit_issue'] ?? null)) {
                return $this->fail('summaries[' . $index . '].fit_issue 必须为字符串');
            }
            $summaryGoods[$goodsId] = true;
        }
        foreach ($placedGoodsIds as $goodsId => $_) {
            if (!isset($summaryGoods[$goodsId])) {
                return $this->fail('已上架商品缺少 summary：' . $goodsId);
            }
        }

        foreach ($layout['unplaced'] as $index => $unplaced) {
            if (!is_array($unplaced)) {
                return $this->fail('unplaced[' . $index . '] 格式错误');
            }
            $goodsId = intval($unplaced['goods_id'] ?? 0);
            if (!isset($selectionIds[$goodsId]) || !is_string($unplaced['reason'] ?? null)) {
                return $this->fail('unplaced[' . $index . '] 商品或原因格式错误');
            }
        }

        foreach (['used_width', 'remaining_width', 'front_row_count', 'total_capacity', 'width_utilization', 'volume_utilization'] as $field) {
            if (!$this->isNonNegativeNumber($layout['stats'][$field] ?? null)) {
                return $this->fail('layout.stats.' . $field . ' 不能小于 0');
            }
        }
        if (floatval($layout['stats']['width_utilization']) > 100
            || floatval($layout['stats']['volume_utilization']) > 100) {
            return $this->fail('layout.stats 利用率不能大于 100');
        }

        foreach ($layout['explanations'] as $index => $explanation) {
            if (!is_string($explanation)) {
                return $this->fail('explanations[' . $index . '] 必须为字符串');
            }
        }

        $availableWidth = 0.0;
        $usedWidth = 0.0;
        foreach ($layout['shelves'] as $shelf) {
            $availableWidth += floatval($shelf['available_width']);
            $usedWidth += floatval($shelf['used_width']);
        }
        $widthUtilization = $availableWidth > 0
            ? round(min(100, $usedWidth / $availableWidth * 100), 2)
            : 0;
        if (intval($layout['stats']['total_capacity']) !== $totalCapacity
            || intval($layout['stats']['front_row_count']) !== count($layout['placements'])) {
            return $this->fail('layout.stats 与 placements 汇总结果不一致');
        }

        return [
            'valid' => true,
            'message' => '',
            'snapshot_json' => $encoded,
            'goods_ids' => array_keys($selectionIds),
            'summary' => [
                'total_capacity' => $totalCapacity,
                'shelf_count' => count($shelfIds),
                'front_row_count' => count($layout['placements']),
                'sku_count' => count($placedGoodsIds),
                'width_utilization' => $widthUtilization,
            ],
        ];
    }

    protected function validateChannel(array $channel, array $template)
    {
        $expected = [
            'machine_type' => (string)$template['machine_type'],
            'width' => intval($template['width']),
            'height' => intval($template['height']),
            'depth' => intval($template['depth']),
            'horizontal_gap' => intval($template['horizontal_gap']),
            'depth_gap' => intval($template['depth_gap']),
            'shelf_gap' => intval($template['shelf_gap']),
            'shelf_thickness' => intval($template['shelf_thickness']),
        ];

        foreach ($expected as $field => $value) {
            if (!array_key_exists($field, $channel)) {
                return $this->fail('snapshot.channel.' . $field . ' 不能为空');
            }
            $actual = $field === 'machine_type'
                ? (string)$channel[$field]
                : intval($channel[$field]);
            if ($actual !== $value) {
                return $this->fail('snapshot.channel 与当前模板参数不一致', 409);
            }
        }
        return ['valid' => true, 'message' => '', 'state' => 200];
    }

    protected function validOrientation($value)
    {
        return in_array($value, ['front', 'rotated'], true);
    }

    protected function isPositiveInteger($value)
    {
        return $this->isInteger($value) && intval($value) > 0;
    }

    protected function isNonNegativeInteger($value)
    {
        return $this->isInteger($value) && intval($value) >= 0;
    }

    protected function isInteger($value)
    {
        return is_numeric($value) && floor(floatval($value)) == floatval($value);
    }

    protected function isPositiveNumber($value)
    {
        return is_numeric($value) && is_finite(floatval($value)) && floatval($value) > 0;
    }

    protected function isNonNegativeNumber($value)
    {
        return is_numeric($value) && is_finite(floatval($value)) && floatval($value) >= 0;
    }

    protected function fail($message, $state = 422)
    {
        return ['valid' => false, 'message' => $message, 'state' => intval($state)];
    }
}
