<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/2
 * Time: 16:26
 */

namespace app\AppFactory\TimeTask\Goods;


use app\AppFactory\AppFactory;
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
     * 修改商品库后，同步修改设备商品库、设备货道，这两个位置修改后会自动触发下发通知设备更新数据
     */
    public function updateGoodsSynchronization()
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
                        $this->synchronizationMgMc($data);
                    }
                }
                usleep(1000);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    /**
     * 修改设备商品库，同步设备货道，修改后会自动触发下发通知设备更新数据
     */
    public function updateMgSynchronization()
    {
        try {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", "6379");
            while (true) {
                $list = $redis->lRange("updateMg", 0, -1);
                $num = count($list);
                if ($num > 0) {
                    $data = $redis->rPop("updateMg");
                    actionLog($data,'修改商品信息后');
                    if ($data) {
                        $this->synchronizationMc($data);
                    }
                }
                usleep(1000);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    /**
     * 修改商品库时，同步到设备商品库跟设备货道
     * @param $g_id
     * @return array|\think\response\Json
     */
    protected function synchronizationMgMc($g_id)
    {
        $goods = $this->getGoodsFind(['g_id' => $g_id],'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,is_recommend,is_gift,recoverable,heat');
        if ($goods) {
            $goods = $goods->toArray();
            actionLog($goods, '需要同步的商品数据');
//            $updateChannel = [
//                "g_name" => $goods['g_name'],
//                "gc_id" => $goods['gc_id'],
//                "gc_name" => $goods['gc_name'],
//                "pic" => $goods['pic'],
//                "sku" => $goods['sku'],
//                "bar_code" => $goods['bar_code'],
//                "cost_price" => $goods['cost_price'],
//                "market_price" => $goods['market_price'],
//                "retail_price" => $goods['retail_price'],
//                "is_recommend" => $goods['is_recommend'],
//                "is_gift" => $goods['is_gift'],
//                "recoverable" => $goods['recoverable'],
//                "heat" => $goods['heat'],
//            ];
//            $this->updateMachineChannel($updateChannel, ['g_id' => $g_id]);
//            $updateMg = [
//                "g_name" => $goods['g_name'],
//                "gc_id" => $goods['gc_id'],
//                "gc_name" => $goods['gc_name'],
//                "pic" => $goods['pic'],
//                "sku" => $goods['sku'],
//                "bar_code" => $goods['bar_code'],
//                "cost_price" => $goods['cost_price'],
//                "market_price" => $goods['market_price'],
//                "retail_price" => $goods['retail_price'],
//            ];
//            $this->updateMachineGoods($updateMg, ['g_id' => $g_id]);
//        }
            $this->startTrans();
            $whereMg['g_id'] = $goods['g_id'];
            $this->synchronizationMachineGoods($whereMg,$goods);

            $whereMc['g_id'] = $goods['g_id'];
            $this->synchronizationMachineChannel($whereMc,$goods);

            $this->commitTrans();
            return $this->rSuccess();
        }
    }

    /**
     * 修改设备商品库后，同步到设备货道
     * @param $mg_id
     */
    protected function synchronizationMc($mg_id)
    {
        $mg = $this->getMachineGoodsFind(['mg_id' => $mg_id],'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price');
        if ($mg) {
            $mg = $mg->toArray();
            $whereMc['mg_id'] = $mg['mg_id'];
            $this->startTrans();
            try {
                $this->synchronizationMachineChannel($whereMc, $mg);
                $this->commitTrans();
            } catch (\Exception $e) {
                $this->rollbackTrans();
                actionException($e,1);
            }
        }
    }

    /**
     * 同步设备商品库数据
     * @param $whereMg
     * @param $goods
     * @return array|\think\response\Json
     */
    protected function synchronizationMachineGoods($whereMg,$goods)
    {
        $machineGoods = $this->getMachineGoodsList($whereMg,0,'mg_id, machine_id');
        if ($machineGoods) {
            $machineGoods = $machineGoods->toArray();
            foreach ($machineGoods as $mgk => $mgv) {
                // 同步设备商品库
                $updateMgResult = $this->updateMachineGoods($goods, ['mg_id' => $mgv['mg_id']],
                    ["g_id", "g_name", "gc_id", "gc_name", "pic", "sku", "bar_code", "cost_price", "market_price", "retail_price"]);
                actionLog($this->getLS(),'修改设备商品库SQL');
                if (!$updateMgResult) {
                    return $this->rFail($this->lang("VMachineGoods.synchronization_fail"));
                }
                $config = [
                    "machine_id" => $mgv['machine_id'],
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                $result = $app->sendMq->triggerUpdateMg($mgv['mg_id']);
                actionLog($result,$mgv['machine_id'] . "设备商品【" . $mgv['mg_id'] . '】更新发送数据结果');
            }
        }
    }

    /**
     * 同步货架商品信息
     * @param $whereMc
     * @param array $goods  需要同步的商品信息
     * @return array|\think\response\Json
     */
    protected function synchronizationMachineChannel($whereMc,$goods)
    {
        $mcList = $this->getMachineChannelList($whereMc, 0, 'mc_id,machine_id,update_price');
        if ($mcList) {
            $mcList = $mcList->toArray();
            foreach ($mcList as $key => $value) {
                $update = $goods;
                // 有手动修改过货道价格的不同步商品价格
                if ($value['update_price'] == 1) {
                    unset($update['cost_price'], $update['market_price'], $update['retail_price']);
                }
                $update['mc_id'] = $value['mc_id'];
                $updateMcResult = $this->updateMachineChannel($update);
                actionLog($this->getLS(),'修改设备货架商品信息SQL');
                if (!$updateMcResult) {
                    return $this->rFail($this->lang("VMachineChannel.synchronization_fail"));
                }
                $config = [
                    "machine_id" => $value['machine_id'],
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                $result = $app->sendMq->triggerUpdateMc($value['mc_id']);
                actionLog($result,$value['machine_id'] . "货架【" . $value['mc_id'] . '】更新发送数据结果');
            }
        }
    }
}