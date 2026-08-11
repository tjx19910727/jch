<?php

namespace app\AppFactory\Kernel\Traits\Machine;

trait MachinePreReplenishmentGoodsChangeTrait
{
    /**
     * 普通货道预补货换品，事务由确认接口统一管理。
     */
    protected function applyPreReplenishmentGoodsChange($mc, $detail, $quantity, $operator = 0)
    {
        $mc = is_object($mc) ? obj2arr($mc) : (array)$mc;
        $detail = is_object($detail) ? obj2arr($detail) : (array)$detail;
        $targetGId = (int)($detail['g_id'] ?? 0);

        if ($targetGId <= 0) {
            throw new \Exception('预补货目标商品不能为空');
        }
        if ((int)($mc['frozen_stock'] ?? 0) > 0) {
            throw new \Exception('当前货道有冻结库存，不允许更换商品');
        }
        if ((int)($mc['out_fail_stock'] ?? 0) > 0) {
            throw new \Exception('当前货道有出货失败库存，不允许更换商品');
        }

        $goods = $this->getGoodsFind(
            ['g_id' => $targetGId],
            'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,intergral_rate,gift_points,cost_points,is_gift,is_recommend,recoverable,heat,release_time,sell_by_date'
        );
        if (!$goods) {
            throw new \Exception('预补货目标商品不存在');
        }
        $goods = is_object($goods) ? $goods->toArray() : (array)$goods;

        $oldStock = (int)($mc['stock'] ?? 0);
        if ($oldStock > 0) {
            $oldChange = [
                'm_id' => $this->machine['m_id'],
                'machine_id' => $this->machine['machine_id'],
                'machine_name' => $this->machine['machine_name'] ?? '',
                'mc_id' => $mc['mc_id'],
                'channel_code' => $mc['channel_code'],
                'mg_id' => $mc['mg_id'] ?? 0,
                'g_id' => $mc['g_id'] ?? 0,
                'g_name' => $mc['g_name'] ?? '',
                'gc_id' => $mc['gc_id'] ?? 0,
                'gc_name' => $mc['gc_name'] ?? '',
                'pic' => $mc['pic'] ?? '',
                'sku' => $mc['sku'] ?? '',
                'bar_code' => $mc['bar_code'] ?? '',
                'change_value' => $oldStock,
                'ao_id' => $this->machine['ao_id'] ?? 0,
                'creator' => $operator,
                'desc' => $this->lang('goodsChange.terminal_exchange_mc_under_old'),
                'position' => 1,
                'type' => 3,
            ];
            if (!$this->addGoodsChange($oldChange)) {
                throw new \Exception('旧商品下架记录写入失败');
            }

            if ((int)($mc['mg_id'] ?? 0) > 0) {
                $reserveChange = $oldChange;
                $reserveChange['desc'] = $this->lang('goodsChange.terminal_exchange_mg_inc_reserve_stock');
                $reserveChange['position'] = 2;
                $reserveChange['type'] = 2;
                if (!$this->addGoodsChange($reserveChange)
                    || !$this->setMachineGoodsInc(['mg_id' => $mc['mg_id']], 'standby_stock', $oldStock)) {
                    throw new \Exception('旧商品退回设备商品库失败');
                }
            }

            $oldRepData = $this->handleRepData($mc, bcsub('0', (string)$oldStock));
            $oldRepData['rep_type'] = 1;
            if (!$this->addMachineChannelReplenishment($oldRepData)) {
                throw new \Exception('旧商品下架补货记录写入失败');
            }
        }

        $targetMgId = 0;
        $targetMachineGoods = $this->getMachineGoodsFind(
            ['m_id' => $this->machine['m_id'], 'g_id' => $targetGId],
            'mg_id,is_shelf'
        );
        if ($targetMachineGoods) {
            $targetMachineGoods = is_object($targetMachineGoods)
                ? $targetMachineGoods->toArray()
                : (array)$targetMachineGoods;
            $targetMgId = (int)$targetMachineGoods['mg_id'];
            if ((int)$targetMachineGoods['is_shelf'] === 2
                && !$this->updateMachineGoods(['mg_id' => $targetMgId, 'is_shelf' => 1])) {
                throw new \Exception('目标商品上架状态更新失败');
            }
        }

        $targetMc = array_merge($mc, $goods, [
            'mg_id' => $targetMgId,
            'stock' => 0,
            'frozen_stock' => 0,
            'out_fail_stock' => 0,
            'batch_number' => '',
            'manufacture_time' => 0,
            'expire_time' => 0,
        ]);

        $newChange = [
            'm_id' => $this->machine['m_id'],
            'machine_id' => $this->machine['machine_id'],
            'machine_name' => $this->machine['machine_name'] ?? '',
            'mc_id' => $targetMc['mc_id'],
            'channel_code' => $targetMc['channel_code'],
            'mg_id' => $targetMc['mg_id'],
            'g_id' => $targetMc['g_id'],
            'g_name' => $targetMc['g_name'],
            'gc_id' => $targetMc['gc_id'],
            'gc_name' => $targetMc['gc_name'],
            'pic' => $targetMc['pic'],
            'sku' => $targetMc['sku'],
            'bar_code' => $targetMc['bar_code'],
            'change_value' => $quantity,
            'ao_id' => $this->machine['ao_id'] ?? 0,
            'creator' => $operator,
            'desc' => $this->lang('goodsChange.terminal_exchange_mc_display_new'),
            'position' => 1,
            'type' => 2,
        ];
        if (!$this->addGoodsChange($newChange)) {
            throw new \Exception('新商品上架记录写入失败');
        }

        $newRepData = $this->handleRepData($targetMc, $quantity);
        $newRepData['rep_type'] = 1;
        if (!$this->addMachineChannelReplenishment($newRepData)) {
            throw new \Exception('新商品补货记录写入失败');
        }

        $targetMc['stock'] = $quantity;
        if (!$this->updateMachineChannel($targetMc)) {
            throw new \Exception('货道商品更新失败');
        }

        return $targetMc;
    }
}
