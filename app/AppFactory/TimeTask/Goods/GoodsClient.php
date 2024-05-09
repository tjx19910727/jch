<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/2
 * Time: 16:26
 */

namespace app\AppFactory\TimeTask\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class GoodsClient extends TimeTaskBase
{
    use GoodsTrait,GoodsCategoryTrait;
    use SaleOrdersTrait;
    use MachineChannelTrait,MachineGoodsTrait;

    /**
     * 同步修改设备商品库、设备货道，这两个位置修改后会自动触发下发通知设备更新数据
     */
    public function synchronizationGoods()
    {
        try {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", "6379");
            while (true) {
                $list = $redis->lRange("updateGoods", 0, -1);
                $num = count($list);
                if ($num > 0) {
                    $data = $redis->rPop("updateGoods");
                    actionLog($data,'修改商品信息后');
                    if ($data) {
                        $this->synchronizationUpdate($data);
                    }
                }
                usleep(1000);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    protected function synchronizationUpdate($g_id)
    {
        $goods = $this->getGoodsFind(['g_id' => $g_id],'g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,is_recommend,is_gift,recoverable,heat');
        if ($goods) {
            actionLog($goods, '需要同步的商品数据');
            $updateChannel = [
                "g_name" => $goods['g_name'],
                "gc_id" => $goods['gc_id'],
                "gc_name" => $goods['gc_name'],
                "pic" => $goods['pic'],
                "sku" => $goods['sku'],
                "bar_code" => $goods['bar_code'],
                "cost_price" => $goods['cost_price'],
                "market_price" => $goods['market_price'],
                "retail_price" => $goods['retail_price'],
                "is_recommend" => $goods['is_recommend'],
                "is_gift" => $goods['is_gift'],
                "recoverable" => $goods['recoverable'],
                "heat" => $goods['heat'],
            ];
            $this->updateMachineChannel($updateChannel, ['g_id' => $g_id]);
            $updateMg = [
                "g_name" => $goods['g_name'],
                "gc_id" => $goods['gc_id'],
                "gc_name" => $goods['gc_name'],
                "pic" => $goods['pic'],
                "sku" => $goods['sku'],
                "bar_code" => $goods['bar_code'],
                "cost_price" => $goods['cost_price'],
                "market_price" => $goods['market_price'],
                "retail_price" => $goods['retail_price'],
            ];
            $this->updateMachineGoods($updateMg, ['g_id' => $g_id]);
        }
    }
}