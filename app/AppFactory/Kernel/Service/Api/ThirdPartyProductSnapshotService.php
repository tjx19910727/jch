<?php

namespace app\AppFactory\Kernel\Service\Api;

use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;

/**
 * 统一 V2 查询接口与第三方推送使用的商品数据口径。
 */
class ThirdPartyProductSnapshotService
{
    public const CORE_AO_ID = 17;

    private const CORE_GOODS_FIELDS = 'g_id product_id,g_name,gc_id,gc_name,`desc`,cost_price,sku,sku2,bar_code,banner,pic,details_pic,retail_price,market_price,status';

    private const MACHINE_INVENTORY_FIELDS = 'mc_id,channel_code,
        (CASE `status` WHEN 3 THEN 0 ELSE stock END) quantity,retail_price sale_price,sku,
        (CASE `status` WHEN 3 THEN stock ELSE 0 END) mismatch_quantity,g_id product_id,g_name,bar_code,cost_price,
        market_price,frozen_stock reserver_quantity,capacity slot_max_count,status';

    private const INVENTORY_GOODS_FIELDS = 'g_id,pic,banner,sku2,sku,bar_code,cost_price,`desc`,retail_price,details_pic,gc_id,gc_name';

    /**
     * 主动同步仅覆盖核心主体 ao_id=17 的设备。
     */
    public function isCoreMachine($machineId)
    {
        return intval(MachineModel::getFieldValue(['machine_id' => $machineId], 'ao_id')) === self::CORE_AO_ID;
    }

    /**
     * 返回与 V2 get_goods_lists 相同字段的商品列表。
     */
    public function getCoreGoodsList($productId = 0, $pagination = null)
    {
        $where = ['ao_id' => self::CORE_AO_ID];
        if ($productId) {
            $where['g_id'] = intval($productId);
        }
        return GoodsModel::getList($where, $pagination, self::CORE_GOODS_FIELDS, 'product_id desc');
    }

    public function getCoreGoodsSnapshot($productId)
    {
        $goods = GoodsModel::getFind(
            ['g_id' => intval($productId), 'ao_id' => self::CORE_AO_ID],
            self::CORE_GOODS_FIELDS
        );
        if (!$goods) {
            return [];
        }
        return method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
    }

    /**
     * 返回与 V2 get_inventory_list 相同字段和计算规则的设备货道列表。
     */
    public function getMachineInventoryList($machineId, $productId = 0, $pagination = null)
    {
        $where = [
            ['machine_id', '=', (string)$machineId],
            ['status', '<>', 2],
        ];
        if ($productId) {
            $where[] = ['g_id', '=', intval($productId)];
        }

        $data = MachineChannelModel::getList(
            $where,
            $pagination,
            self::MACHINE_INVENTORY_FIELDS,
            'stock desc'
        );
        return $this->enrichMachineInventory($data);
    }

    public function getMachineInventorySnapshot($machineId)
    {
        $data = $this->getMachineInventoryList($machineId, 0, null);
        if (!$data) {
            return [];
        }
        return method_exists($data, 'toArray') ? array_values($data->toArray()) : array_values((array)$data);
    }

    private function enrichMachineInventory($data)
    {
        if (!$data) {
            return $data;
        }

        $productIds = [];
        foreach ($data as $item) {
            $productId = intval($item['product_id'] ?? 0);
            if ($productId > 0) {
                $productIds[] = $productId;
            }
        }

        $goodsMap = [];
        if ($productIds) {
            $goodsList = GoodsModel::getList([
                ['g_id', 'in', array_values(array_unique($productIds))],
                ['sell_channel', 'in', [1, 3]],
            ], null, self::INVENTORY_GOODS_FIELDS);
            foreach ($goodsList as $goods) {
                $goodsMap[intval($goods['g_id'])] = method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
            }
        }

        return $data->each(function ($item) use ($goodsMap) {
            $goods = $goodsMap[intval($item['product_id'] ?? 0)] ?? [];
            $item['g_retail_price'] = $goods['retail_price'] ?? 0;
            $item['pic'] = $goods['pic'] ?? '';
            $item['details_pic'] = $goods['details_pic'] ?? '';
            $item['banner'] = $goods['banner'] ?? '';
            $item['sku'] = $goods['sku'] ?? '';
            $item['sku2'] = $goods['sku2'] ?? '';
            $item['bar_code'] = $goods['bar_code'] ?? '';
            $item['cost_price'] = $goods['cost_price'] ?? '';
            $item['g_desc'] = $goods['desc'] ?? '';
            $item['gc_id'] = $goods['gc_id'] ?? '';
            $item['gc_name'] = $goods['gc_name'] ?? '';
            return $item;
        });
    }
}
