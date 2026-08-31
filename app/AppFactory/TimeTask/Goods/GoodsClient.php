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
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\TimeTask\TimeTaskBase;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use think\facade\Cache;

class GoodsClient extends TimeTaskBase
{
    use GoodsTrait,GoodsCategoryTrait;
    use SaleOrdersTrait;
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait;

    /**
     * 修改商品库后，同步修改设备商品库、设备货道，这两个位置修改后会自动触发下发通知设备更新数据
     */
    public function updateGoodsSynchronization()
    {
        try {
            $redis = new \Redis();
            $config = config("redis");
            $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
            if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
            while (true) {
                $list = $redis->lRange("updateGoods", 0, -1);
                $num = $list ? count($list) : 0;
                if ($num > 0) {
                    $data = $redis->rPop("updateGoods");
                    if ($data) {
                        actionLog($data,'修改商品信息后','updateGoodsSynchronization');
                        $task = json_decode($data, true);
                        if (is_array($task)
                            && (int)($task['version'] ?? 0) === 2
                            && ($task['type'] ?? '') === 'goods_price_update') {
                            if (!empty($task['mg_ids']) && is_array($task['mg_ids'])) {
                                $this->synchronizationGoodsV2($task);
                            }
                            if (!empty($task['mc_ids']) && is_array($task['mc_ids'])) {
                                $this->synchronizationMgMcV2($task);
                            }
                        } else {
                            $this->synchronizationGoods($data);
                            $this->synchronizationMgMc($data);
                        }
                    }
                }
                sleep(1);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
        return "处理完成";
    }

    /**
     * 修改设备商品库，同步设备货道，修改后会自动触发下发通知设备更新数据
     */
    public function updateMgSynchronization()
    {
        try {
            $redis = new \Redis();
            $config = config("redis");
            $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
            if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
            while (true) {
                $list = $redis->lRange("updateMg", 0, -1);
                $num = $list ? count($list) : 0;
                if ($num > 0) {
                    $data = $redis->rPop("updateMg");
                    actionLog($data,'修改商品信息后','updateMgSynchronization');
                    if ($data) {
                        $this->synchronizationMc($data);
                    }
                }
                sleep(1);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
        return "处理完成";
    }

    public function testUpdateMg()
    {
        $data = "444";
        $this->synchronizationMc($data);
    }

    /**
     * 修改商品库，通知设备更新该商品
     * @param $g_id
     */
    protected function synchronizationGoods($g_id)
    {
        $ao_id = $this->getGoodsValue(['g_id' => $g_id],"ao_id");
        actionLog($ao_id,'商品组织架构ID','synchronizationGoods');
        if ($ao_id) {
            $machine_ids = $this->getMachineColumn(['ao_id' => $ao_id,'status' => 1, 'online' => 1], 'machine_id');
            actionLog($this->getLS(),'查询设备编号SQL','synchronizationGoods');
            actionLog($machine_ids,'可下发设备编号','synchronizationGoods');
            if ($machine_ids) {
                foreach ($machine_ids as $machine_id) {
                    $this->sendToMachine(['machine_id' => $machine_id], 'updateGoods', ['g_id' => $g_id]);
                }
            }
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
            actionLog($goods, '需要同步的商品数据','synchronizationMgMc');
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
            try {
                $whereMg['g_id'] = $goods['g_id'];
                $this->synchronizationMachineGoods($whereMg, $goods);
                $whereMc['g_id'] = $goods['g_id'];
                $this->synchronizationMachineChannel($whereMc, $goods);
                $this->commitTrans();
                return $this->rSuccess();
            } catch (\Exception $e) {
                $this->rollbackTrans();
                actionException($e,1);
                return $this->rTryCatch($e->getMessage());
            }
        }
    }

    /**
     * 商品价格覆盖后，只通知本次勾选记录所在的设备更新商品库。
     *
     * @param array $task
     * @return int
     */
    protected function synchronizationGoodsV2($task)
    {
        $gId = (int)($task['g_id'] ?? 0);
        if ($gId <= 0) {
            return 0;
        }

        $mgIds = $this->filterGoodsUpdateV2Ids($task['mg_ids'] ?? []);
        if (!$mgIds) {
            return 0;
        }

        $machineIds = $this->getMachineGoodsColumn([
            ['mg_id', 'in', $mgIds],
        ], 'machine_id') ?: [];
        $machineIds = array_values(array_unique(array_filter($machineIds)));
        if (!$machineIds) {
            return 0;
        }

        $sendCount = 0;
        foreach ($machineIds as $machineId) {
            $result = $this->sendToMachine(['machine_id' => $machineId], 'updateGoods', ['g_id' => $gId]);
            actionLog($result, $machineId . '商品库V2更新发送结果', 'synchronizationGoodsV2');
            $sendCount++;
        }
        return $sendCount;
    }

    /**
     * 商品价格覆盖后，只通知本次勾选的货道更新。
     * 价格数据已由管理端编辑接口更新，此处不再覆盖数据库。
     *
     * @param array $task
     * @return array
     */
    protected function synchronizationMgMcV2($task)
    {
        $resultCount = [
            'machine_channel_count' => 0,
        ];

        $mcIds = $this->filterGoodsUpdateV2Ids($task['mc_ids'] ?? []);
        if ($mcIds) {
            $machineChannels = $this->getMachineChannelList([
                ['mc_id', 'in', $mcIds],
            ], 0, 'mc_id,machine_id');
            if ($machineChannels) {
                foreach ($machineChannels->toArray() as $machineChannelItem) {
                    $result = $this->sendToMachine(
                        ['machine_id' => $machineChannelItem['machine_id']],
                        'updateMc',
                        ['mc_id' => $machineChannelItem['mc_id']]
                    );
                    actionLog($result, $machineChannelItem['machine_id'] . '货道V2更新发送结果', 'synchronizationMgMcV2');
                    $resultCount['machine_channel_count']++;
                }
            }
        }

        actionLog([
            'task' => $task,
            'result' => $resultCount,
        ], '商品价格覆盖V2同步完成', 'synchronizationMgMcV2');
        return $resultCount;
    }

    /**
     * @param mixed $ids
     * @return array
     */
    protected function filterGoodsUpdateV2Ids($ids)
    {
        if (!is_array($ids)) {
            return [];
        }
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });
        return array_values($ids);
    }

    /**
     * 修改设备商品库后，同步到设备货道
     * @param $mg_id
     */
    public function synchronizationMc($mg_id)
    {
        $mg = $this->getMachineGoodsFind(['mg_id' => $mg_id],'mg_id,machine_id,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price');
        if ($mg) {
            $mg = $mg->toArray();
            $whereMc['g_id'] = $mg['g_id'];
            $whereMc['machine_id'] = $mg['machine_id'];
            $result = $this->sendToMachine(['machine_id' => $mg['machine_id']],'updateMg',['mg_id' => $mg['mg_id']]);
            actionLog($result,'推送设备商品库','synchronizationMc');

            unset($mg['machine_id'],$mg['mg_id']);
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
        actionLog($this->getLS(),'查询设备商品数据SQL','synchronizationMachineGoods');
        if ($machineGoods) {
            $machineGoods = $machineGoods->toArray();
            actionLog($machineGoods,'绑定该商品的所有设备商品','synchronizationMachineGoods');
            foreach ($machineGoods as $mgk => $mgv) {
                // 同步设备商品库
                //20260821注释
                // $updateMgResult = $this->updateMachineGoods($goods, ['mg_id' => $mgv['mg_id']],
                //     ["g_id", "g_name", "gc_id", "gc_name", "pic", "sku", "bar_code", "cost_price", "market_price", "retail_price"]);
                // actionLog($this->getLS(),'修改设备商品库SQL','synchronizationMachineGoods');
                // if (!$updateMgResult) {
                //     return $this->rFail($this->lang("VMachineGoods.synchronization_fail"));
                // }
                $result = $this->sendToMachine(['machine_id' => $mgv['machine_id']],'updateMg',['mg_id' => $mgv['mg_id']]);
                actionLog($result,$mgv['machine_id'] . "设备商品【" . $mgv['mg_id'] . '】更新发送数据结果','synchronizationMachineGoods');
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
                //20260821注释，已经不需要从此处修改货道的价格
                // if ($value['update_price'] == 1) {
                //     unset($update['cost_price'], $update['market_price'], $update['retail_price']);
                // }
                // $update['mc_id'] = $value['mc_id'];
                // $updateMcResult = $this->updateMachineChannel($update);
                // actionLog($this->getLS(),'修改设备货架商品信息SQL','synchronizationMachineChannel');
                // if (!$updateMcResult) {
                //     return $this->rFail($this->lang("VMachineChannel.synchronization_fail"));
                // }
                $result = $this->sendToMachine(['machine_id' => $value['machine_id']],'updateMc',['mc_id' => $value['mc_id']]);
                actionLog($result,$value['machine_id'] . "货架【" . $value['mc_id'] . '】更新发送数据结果','synchronizationMachineChannel');
            }
        }
    }

    /**
     * 检查货道商品过期/快到期提醒
     * 每天执行一次，每个货道每天只发一次通知
     * php think time_task goods checkGoodsExpiry
     */
    public function checkGoodsExpiry()
    {
        $today = date('Ymd');
        $now = time();
        $tomorrowStart = strtotime('tomorrow');

        // 货道表连商品表，筛选 expire_time>0 且 goods.expire_notice>0 的货道
        $expireList = MachineChannelModel::alias('mc')
            ->join('goods g', 'g.g_id = mc.g_id', 'left')
            ->where('mc.expire_time', '>', 0)
            ->where('mc.g_id', '>', 0)
            ->where('g.expire_notice', '>', 0)
            ->field('mc.mc_id,mc.m_id,mc.machine_id,mc.channel_code,mc.g_id,mc.g_name,mc.expire_time,g.expire_notice')
            ->select();

        if (!$expireList || count($expireList) === 0) {
            return '无过期时间数据';
        }
        $expireList = $expireList->toArray();

        $sendCount = 0;
        foreach ($expireList as $mc) {
            $cacheKey = 'goods_expiry_notice:' . $mc['mc_id'] . ':' . $today;
            if (Cache::get($cacheKey)) {
                continue;
            }

            $isExpired = $mc['expire_time'] < $now;
            $isNearExpiry = !$isExpired && ($mc['expire_time'] - $mc['expire_notice'] * 86400) < $now;

            if (!$isExpired && !$isNearExpiry) {
                continue;
            }

            $machineName = $this->getMachineValue(['m_id' => $mc['m_id']], 'machine_name') ?? $mc['machine_id'];
            $aoId = $this->getMachineValue(['m_id' => $mc['m_id']], 'ao_id') ?? 0;

            if ($isExpired) {
                $errorCode = '货道商品已过期';
                $errorInfo = 11102012;
                $exceptionDeclaration = '货道商品已过期，请及时处理';
            } else {
                $remainDays = ceil(($mc['expire_time'] - $now) / 86400);
                $errorCode = '货道商品即将过期';
                $errorInfo = 11102013;
                $exceptionDeclaration = "货道商品将于{$remainDays}天后过期";
            }

            $this->noticeSendData = [
                'ao_id'        => $aoId,
                'm_id'         => $mc['m_id'],
                'templateType' => 'mFault',
                'replaceData'  => [
                    'errorCode'             => $errorCode,
                    'error_code'            => $errorCode,
                    'error_time'            => date('Y-m-d H:i:s'),
                    'error_info'            => $errorInfo,
                    'date'                  => date('Y年m月d日'),
                    'exceptionDeclaration'  => $exceptionDeclaration,
                    'machine_id'            => $mc['machine_id'],
                    'machine_name'          => mb_substr($machineName, 0, 20, 'UTF-8'),
                    'channel_code'          => $mc['channel_code'],
                    'g_name'                => $mc['g_name'],
                ],
            ];

            $this->noticeSend();
            Cache::set($cacheKey, 1, $tomorrowStart - $now);
            $sendCount++;

            actionLog($mc, $isExpired ? '发送过期通知' : '发送快到期通知', 'checkGoodsExpiry');
        }

        return "处理完成，发送通知数：{$sendCount}";
    }
}
