<?php

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Goods\GoodsModel;

trait MachinePreReplenishmentGoodsSnapshotTrait
{
    protected function formatPreReplenishmentGoods($goods)
    {
        $goods = is_object($goods) ? obj2arr($goods) : (array)$goods;
        $pic = $goods['pic'] ?? ($goods['image_url'] ?? '');

        return [
            'g_id' => (int)($goods['g_id'] ?? 0),
            'sku' => $goods['sku'] ?? '',
            'g_name' => $goods['g_name'] ?? '',
            'pic' => $pic,
            'image_url' => $pic,
            'bar_code' => $goods['bar_code'] ?? '',
        ];
    }

    protected function getPreReplenishmentGoodsMap($gIds)
    {
        $gIds = array_values(array_unique(array_filter(array_map('intval', (array)$gIds))));
        if (!$gIds) {
            return [];
        }

        $goodsList = GoodsModel::where([['g_id', 'in', $gIds]])
            ->field('g_id,g_name,pic,sku,bar_code')
            ->select()
            ->toArray();

        return array_column($goodsList, null, 'g_id');
    }

    protected function resolvePreReplenishmentGoodsContext($detail, $sourceGoods, $goodsMap = [])
    {
        $detail = is_object($detail) ? obj2arr($detail) : (array)$detail;
        $sourceGoods = is_object($sourceGoods) ? obj2arr($sourceGoods) : (array)$sourceGoods;
        $sourceGId = (int)($sourceGoods['g_id'] ?? 0);

        $beforeGId = array_key_exists('before_g_id', $detail) && (int)$detail['before_g_id'] > 0
            ? (int)$detail['before_g_id']
            : $sourceGId;
        $beforeSku = array_key_exists('before_sku', $detail) && $detail['before_sku'] !== ''
            ? (string)$detail['before_sku']
            : (string)($sourceGoods['sku'] ?? '');
        $targetGId = (int)($detail['g_id'] ?? 0);
        if ($targetGId <= 0) {
            $targetGId = $beforeGId;
        }

        $beforeGoodsSource = $goodsMap[$beforeGId] ?? array_merge($sourceGoods, [
            'g_id' => $beforeGId,
            'sku' => $beforeSku,
        ]);
        $targetGoodsSource = $goodsMap[$targetGId] ?? [
            'g_id' => $targetGId,
            'sku' => $detail['sku'] ?? '',
        ];
        if ($targetGId === $sourceGId) {
            $targetGoodsSource = $goodsMap[$targetGId] ?? $sourceGoods;
        }

        return [
            'before_g_id' => $beforeGId,
            'before_sku' => $beforeSku,
            'g_id' => $targetGId,
            'is_change_goods' => $targetGId !== $beforeGId,
            'before_goods' => $this->formatPreReplenishmentGoods($beforeGoodsSource),
            'target_goods' => $this->formatPreReplenishmentGoods($targetGoodsSource),
        ];
    }
}
