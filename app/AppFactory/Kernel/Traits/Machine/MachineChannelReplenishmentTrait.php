<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/1
 * Time: 18:02
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Goods\GoodsChangeModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelReplenishmentModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VChannelReplenishment;
use think\facade\Db;

trait MachineChannelReplenishmentTrait
{
    use MachinePreReplenishmentTrait;

    public function getMachineChannelReplenishmentFind($where, $field = "*", $order = "")
    {
        return MachineChannelReplenishmentModel::getFind($where, $field, $order);
    }

    public function getMachineChannelReplenishmentList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineChannelReplenishmentModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineChannelReplenishment($insert)
    {
        $data = MachineChannelReplenishmentModel::create($insert);
        return $data->id;
    }

    public function delMachineChannelReplenishment($where)
    {
        $result = MachineChannelReplenishmentModel::whereDel($where);
        return $result;
    }

    /**
     * 终端补货上报
     * @return mixed
     */
    public function terminalReplenishment()
    {
        $amm = $this->getAuthManagerMachineColumn(['m_id' => $this->machine['m_id']], 'manager_id');
        if (!in_array($this->data['operator'],$amm)) {
            return $this->rFail($this->lang("VChannelReplenishment.non-administrators"));
        }

        $this->data['repList'] = json2arr($this->data['repList']);
        if (!is_array($this->data['repList']) || !$this->data['repList']) {
            return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
        }

        $repMap = [];
        foreach ($this->data['repList'] as $value) {
            try {
                validate(VChannelReplenishment::class)->scene("repList")->check($value);
            } catch (\Exception $e) {
                actionException($e, 1);
                return $this->rValidate($this->lang($e->getMessage()));
            }

            $mcId = (int)($value['mc_id'] ?? 0);
            if (!$mcId) {
                return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
            }
            if (!isset($repMap[$mcId])) {
                $repMap[$mcId] = [
                    'mc_id' => $mcId,
                    'quantity' => 0,
                    'standby_quantity' => 0,
                    'batch_arr' => [],
                ];
            }
            $repMap[$mcId]['quantity'] += (int)($value['quantity'] ?? 0);
            $repMap[$mcId]['standby_quantity'] += (int)($value['standby_quantity'] ?? 0);
            // ==================== 单货道多商品相关开始 ====================
            $batchArr = $this->normalizeReplenishmentBatchArr($value['batch_arr'] ?? []);
            if ($batchArr) {
                foreach ($batchArr as $batchItem) {
                    $batchId = (int)($batchItem['batch_id'] ?? 0);
                    if ($batchId <= 0) {
                        return $this->rFail('多商品补货必须传入有效批次ID');
                    }
                    if (!isset($repMap[$mcId]['batch_arr'][$batchId])) {
                        $repMap[$mcId]['batch_arr'][$batchId] = [
                            'batch_id' => $batchId,
                            'quantity' => 0,
                            'standby_quantity' => 0,
                            'g_id' => (int)($batchItem['g_id'] ?? 0),
                        ];
                    }
                    $repMap[$mcId]['batch_arr'][$batchId]['quantity'] += (int)($batchItem['quantity'] ?? 0);
                    $repMap[$mcId]['batch_arr'][$batchId]['standby_quantity'] += (int)($batchItem['standby_quantity'] ?? 0);
                }
            }
            // ==================== 单货道多商品相关结束 ====================
        }

        if (!$repMap) {
            return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
        }

        $flag = [];
        $goodsChangeRows = [];
        $repRows = [];
        $this->startTrans();
        try {
            $mcIds = array_keys($repMap);
            $mcField = 'mc_id,m_id,channel_code,capacity,stock,frozen_stock,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number,is_multi_goods';
            $mcList = MachineChannelModel::where([
                ['m_id', '=', $this->machine['m_id']],
                ['mc_id', 'in', $mcIds],
            ])->field($mcField)->lock(true)->select()->toArray();
            $mcMap = array_column($mcList, null, 'mc_id');

            if (count($mcMap) != count($mcIds)) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
            }

            $mgIds = [];
            foreach ($repMap as $repItem) {
                if ((int)$repItem['standby_quantity'] != 0) {
                    $mgId = (int)($mcMap[$repItem['mc_id']]['mg_id'] ?? 0);
                    if ($mgId > 0) $mgIds[] = $mgId;
                }
            }
            $mgMap = [];
            if ($mgIds) {
                $mgField = 'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,standby_stock';
                $mgList = MachineGoodsModel::where([['mg_id', 'in', array_values(array_unique($mgIds))]])->field($mgField)->select()->toArray();
                $mgMap = array_column($mgList, null, 'mg_id');
            }

            $insertGChange = [
                "m_id" => $this->machine['m_id'],
                "machine_id" => $this->machine['machine_id'],
                "machine_name" => $this->machine['machine_name'],
                "ao_id" => $this->machine['ao_id'],
                "creator" => $this->data['operator'],
            ];
            foreach ($repMap as $value) {
                $insertGc = $insertGChange;
                $mc = $mcMap[$value['mc_id']];
                // ==================== 单货道多商品相关开始 ====================
                if (intval($mc['is_multi_goods'] ?? 2) === 1) {
                    $result = $this->handleMultiGoodsReplenishment($mc, $value, $insertGChange, $goodsChangeRows, $repRows, $flag);
                    if ($result !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($result);
                    }
                    continue;
                }
                if (!empty($value['batch_arr'])) {
                    $this->rollbackTrans();
                    return $this->rFail('当前货道未开启多商品模式');
                }
                // ==================== 单货道多商品相关结束 ====================
                // 货道库存+补货数量+冻结库存 不能超过货道容量
                $quantity = $mc['stock'] + $value['quantity'] + $mc['frozen_stock'];
                if (isset($value['standby_quantity'])) $quantity += $value['standby_quantity'];
                if ($quantity > $mc['capacity']) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VChannelReplenishment.exceed_capacity_limit"));
                }
                $insertGc = array_merge($insertGc, [
                    "mc_id" => $mc['mc_id'],
                    "channel_code" => $mc['channel_code'],
                    "mg_id" => $mc['mg_id'],
                    "g_id" => $mc['g_id'],
                    "g_name" => $mc['g_name'],
                    "gc_id" => $mc['gc_id'],
                    "gc_name" => $mc['gc_name'],
                    "pic" => $mc['pic'],
                    "sku" => $mc['sku'],
                    "bar_code" => $mc['bar_code'],
                ]);
                // 补货时使用了备用库存
                if (isset($value['standby_quantity']) && $mc['mg_id'] > 0 && $value['standby_quantity'] != 0) {
                    $mg = $mgMap[$mc['mg_id']] ?? [];
                    if (!$mg) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VChannelReplenishment.mg_no_data") . $mc['channel_code']);
                    }
                    if ($mg['standby_stock'] < $value['standby_quantity']) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VChannelReplenishment.exceed_standby_stock_limit"));
                    }
                    $desc = $this->lang("goodsChange.terminal_rep_inc_mg_reserve_stock");
                    $channelDesc = $this->lang("goodsChange.terminal_rep_dec_mc_reserve_stock");
                    $type = 3;
                    if ($value['standby_quantity'] > 0) {
                        $desc = $this->lang("goodsChange.terminal_rep_dec_mg_reserve_stock");
                        $channelDesc = $this->lang("goodsChange.terminal_rep_inc_mc_reserve_stock");
                        $type = 2;
                    }

                    $insertGc['type'] = $type;
                    $insertGc["change_value"] = abs($value['standby_quantity']);

                    // 记录商品变化事件（设备商品库备用库存）
                    $insertGc['desc'] = $desc;
                    $insertGc['position'] = 2;
                    $goodsChangeRows[] = $insertGc;

                    // 记录商品变化事件（货架库存）
                    $insertGc['desc'] = $channelDesc;
                    $insertGc['position'] = 1;
                    $goodsChangeRows[] = $insertGc;

                    // 生成备用库存补货记录
                    $repData = $this->handleRepData($mc, $value['standby_quantity']);
                    $repData['rep_type'] = 2;
                    $repRows[] = $repData;
                    $mc['stock'] += $value['standby_quantity'];

                    // 修改设备商品库备用库存数
                    $flag[] = $value['standby_quantity'] > 0 ?
                        $this->setMachineGoodsDec(['mg_id' => $mg['mg_id']], 'standby_stock', $value['standby_quantity'])
                        : $this->setMachineGoodsInc(['mg_id' => $mg['mg_id']], 'standby_stock', abs($value['standby_quantity']));

                    $mgMap[$mg['mg_id']]['standby_stock'] = $value['standby_quantity'] > 0
                        ? ($mgMap[$mg['mg_id']]['standby_stock'] - $value['standby_quantity'])
                        : ($mgMap[$mg['mg_id']]['standby_stock'] + abs($value['standby_quantity']));
                }

                if ($value['quantity'] != 0) {
                    // 记录商品变化事件（货架上架补货）
                    $channelDesc = $this->lang("goodsChange.terminal_rep_inc_mc_stock");
                    if ($value['quantity'] < 0) $channelDesc = $this->lang("goodsChange.terminal_rep_dec_mc_stock");
                    $insertGc['desc'] = $channelDesc;
                    $insertGc['position'] = 1;
                    $insertGc["change_value"] = abs($value['quantity']);
                    $insertGc['type'] = ($value['quantity'] > 0 ? 2 : 3);
                    $goodsChangeRows[] = $insertGc;

                    // 生成上架补货记录
                    $repData = $this->handleRepData($mc, $value['quantity']);
                    $repRows[] = $repData;
                    $mc['stock'] += $value['quantity'];
                }

                $recordNo = $this->data['record_no'] ?? '';
                if ($recordNo) {
                    $syncQuantity = $value['quantity'];
                    if (isset($value['standby_quantity'])) {
                        $syncQuantity += $value['standby_quantity'];
                    }
                    if ($syncQuantity != 0) {
                        $flag[] = $this->syncByTerminalReplenishmentRecordNo($recordNo, $mc, $syncQuantity, $this->data);
                    }
                }

                $flag[] = $this->updateMachineChannel(['mc_id' => $mc['mc_id'], 'stock' => $mc['stock']]);
                $mcMap[$mc['mc_id']]['stock'] = $mc['stock'];
            }

            if ($goodsChangeRows) {
                foreach (array_chunk($goodsChangeRows, 500) as $batch) {
                    actionLog(GoodsChangeModel::fetchSql()->insertAll($batch), 'goods_change SQL', 'replenishment_sql');
                    $flag[] = GoodsChangeModel::insertAll($batch);
                }
            }

            if ($repRows) {
                foreach (array_chunk($repRows, 500) as $batch) {
                    actionLog(MachineChannelReplenishmentModel::fetchSql()->insertAll($batch), 'machine_channel_replenishment SQL', 'replenishment_sql');
                    $flag[] = MachineChannelReplenishmentModel::insertAll($batch);
                }
            }

            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    // ==================== 单货道多商品相关开始 ====================
    /**
     * 解析终端上报的多商品补货批次数组。
     */
    private function normalizeReplenishmentBatchArr($batchArr)
    {
        if (is_string($batchArr)) {
            $batchArr = json2arr($batchArr);
        }
        return is_array($batchArr) ? $batchArr : [];
    }

    /**
     * 处理单货道多商品补货。
     */
    private function handleMultiGoodsReplenishment($mc, $value, $insertGChange, &$goodsChangeRows, &$repRows, &$flag)
    {
        $batchItems = array_values($value['batch_arr'] ?? []);

        // 顶层 quantity 固定表示正在售卖的队首批次，batch_arr 只表示非队首批次。
        if ((int)$value['quantity'] != 0 || (int)$value['standby_quantity'] != 0) {
            $headBatch = Db::name('channel_goods_batch')
                ->where('mc_id', $mc['mc_id'])
                ->where('status', 1)
                ->lock(true)
                ->find();
            if (!$headBatch) {
                return '多商品货道未找到队首批次';
            }
            array_unshift($batchItems, [
                'batch_id' => $headBatch['batch_id'],
                'quantity' => (int)$value['quantity'],
                'standby_quantity' => (int)$value['standby_quantity'],
                'is_head' => 1,
            ]);
        }

        if (!$batchItems) {
            return true;
        }

        $batchIds = array_values(array_unique(array_column($batchItems, 'batch_id')));
        $batchList = Db::name('channel_goods_batch')
            ->where('mc_id', $mc['mc_id'])
            ->whereIn('batch_id', $batchIds)
            ->whereIn('status', [1, 2, 3])
            ->lock(true)
            ->select()
            ->toArray();
        $batchMap = array_column($batchList, null, 'batch_id');
        if (count($batchMap) != count($batchIds)) {
            return '多商品补货批次不存在';
        }

        $gIds = array_values(array_unique(array_column($batchList, 'g_id')));
        $goodsMap = [];
        if ($gIds) {
            $goodsList = GoodsModel::where([['g_id', 'in', $gIds]])
                ->field('g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price')
                ->select()
                ->toArray();
            $goodsMap = array_column($goodsList, null, 'g_id');
        }

        $mgMap = [];
        if ($gIds) {
            $mgList = MachineGoodsModel::where([
                ['m_id', '=', $this->machine['m_id']],
                ['g_id', 'in', $gIds],
            ])->field('mg_id,g_id,standby_stock')->select()->toArray();
            $mgMap = array_column($mgList, null, 'g_id');
        }

        foreach ($batchItems as $batchItem) {
            $batchId = (int)$batchItem['batch_id'];
            $batch = $batchMap[$batchId];
            if (empty($batchItem['is_head']) && (int)$batch['status'] === 1) {
                return 'batch_arr只能传非队首批次';
            }
            if (!empty($batchItem['g_id']) && (int)$batchItem['g_id'] !== (int)$batch['g_id']) {
                return '多商品补货批次商品不匹配';
            }

            $quantity = (int)($batchItem['quantity'] ?? 0);
            $standbyQuantity = (int)($batchItem['standby_quantity'] ?? 0);
            if ($quantity == 0 && $standbyQuantity == 0) {
                continue;
            }

            $newStock = (int)$batch['stock'] + $quantity + $standbyQuantity;
            if ($newStock + (int)$batch['frozen_stock'] > (int)$batch['capacity']) {
                return $this->lang("VChannelReplenishment.exceed_capacity_limit");
            }

            $goods = $goodsMap[$batch['g_id']] ?? [];
            $mg = $mgMap[$batch['g_id']] ?? [];
            $batchMc = array_merge($mc, [
                'mg_id' => $mg['mg_id'] ?? 0,
                'g_id' => $batch['g_id'],
                'g_name' => $goods['g_name'] ?? '',
                'gc_id' => $goods['gc_id'] ?? 0,
                'gc_name' => $goods['gc_name'] ?? '',
                'pic' => $goods['pic'] ?? '',
                'sku' => $goods['sku'] ?? '',
                'bar_code' => $goods['bar_code'] ?? '',
                'batch_number' => $batch['batch_number'] ?? '',
                'stock' => $batch['stock'],
                'capacity' => $batch['capacity'],
            ]);

            if ($standbyQuantity != 0) {
                if (!$mg) {
                    return $this->lang("VChannelReplenishment.mg_no_data") . $mc['channel_code'];
                }
                if ($standbyQuantity > 0 && (int)$mg['standby_stock'] < $standbyQuantity) {
                    return $this->lang("VChannelReplenishment.exceed_standby_stock_limit");
                }

                $desc = $this->lang("goodsChange.terminal_rep_inc_mg_reserve_stock");
                $channelDesc = $this->lang("goodsChange.terminal_rep_dec_mc_reserve_stock");
                $type = 3;
                if ($standbyQuantity > 0) {
                    $desc = $this->lang("goodsChange.terminal_rep_dec_mg_reserve_stock");
                    $channelDesc = $this->lang("goodsChange.terminal_rep_inc_mc_reserve_stock");
                    $type = 2;
                }

                $insertGc = array_merge($insertGChange, [
                    "mc_id" => $batchMc['mc_id'],
                    "channel_code" => $batchMc['channel_code'],
                    "mg_id" => $batchMc['mg_id'],
                    "g_id" => $batchMc['g_id'],
                    "g_name" => $batchMc['g_name'],
                    "gc_id" => $batchMc['gc_id'],
                    "gc_name" => $batchMc['gc_name'],
                    "pic" => $batchMc['pic'],
                    "sku" => $batchMc['sku'],
                    "bar_code" => $batchMc['bar_code'],
                    "type" => $type,
                    "change_value" => abs($standbyQuantity),
                ]);

                $insertGc['desc'] = $desc;
                $insertGc['position'] = 2;
                $goodsChangeRows[] = $insertGc;

                $insertGc['desc'] = $channelDesc;
                $insertGc['position'] = 1;
                $goodsChangeRows[] = $insertGc;

                $repData = $this->handleRepData($batchMc, $standbyQuantity);
                $repData['rep_type'] = 2;
                $repRows[] = $repData;

                $flag[] = $standbyQuantity > 0 ?
                    $this->setMachineGoodsDec(['mg_id' => $mg['mg_id']], 'standby_stock', $standbyQuantity)
                    : $this->setMachineGoodsInc(['mg_id' => $mg['mg_id']], 'standby_stock', abs($standbyQuantity));

                $mgMap[$batch['g_id']]['standby_stock'] = $standbyQuantity > 0
                    ? ((int)$mgMap[$batch['g_id']]['standby_stock'] - $standbyQuantity)
                    : ((int)$mgMap[$batch['g_id']]['standby_stock'] + abs($standbyQuantity));
            }

            if ($quantity != 0) {
                $insertGc = array_merge($insertGChange, [
                    "mc_id" => $batchMc['mc_id'],
                    "channel_code" => $batchMc['channel_code'],
                    "mg_id" => $batchMc['mg_id'],
                    "g_id" => $batchMc['g_id'],
                    "g_name" => $batchMc['g_name'],
                    "gc_id" => $batchMc['gc_id'],
                    "gc_name" => $batchMc['gc_name'],
                    "pic" => $batchMc['pic'],
                    "sku" => $batchMc['sku'],
                    "bar_code" => $batchMc['bar_code'],
                    "desc" => $quantity > 0 ? $this->lang("goodsChange.terminal_rep_inc_mc_stock") : $this->lang("goodsChange.terminal_rep_dec_mc_stock"),
                    "position" => 1,
                    "change_value" => abs($quantity),
                    "type" => ($quantity > 0 ? 2 : 3),
                ]);
                $goodsChangeRows[] = $insertGc;

                $repRows[] = $this->handleRepData($batchMc, $quantity);
            }

            $status = (int)$batch['status'];
            if ($status !== 1) {
                $status = ($newStock + (int)$batch['frozen_stock']) > 0 ? 2 : 3;
            }
            $flag[] = Db::name('channel_goods_batch')
                ->where('batch_id', $batchId)
                ->update(['stock' => $newStock, 'status' => $status]);

            $recordNo = $this->data['record_no'] ?? '';
            $syncQuantity = $quantity + $standbyQuantity;
            if ($recordNo && $syncQuantity != 0) {
                $batchMc['stock'] = $newStock;
                $flag[] = $this->syncByTerminalReplenishmentRecordNo($recordNo, $batchMc, $syncQuantity, $this->data);
            }
        }

        $flag[] = $this->syncMultiGoodsHeadToMachineChannel($mc['mc_id']);
        return true;
    }

    /**
     * 将多商品队首批次同步回 machine_channel。
     */
    private function syncMultiGoodsHeadToMachineChannel($mcId)
    {
        $headBatch = Db::name('channel_goods_batch')
            ->where('mc_id', $mcId)
            ->where('status', 1)
            ->order('sequence asc')
            ->find();
        if (!$headBatch) {
            return true;
        }

        $goods = GoodsModel::getFind(
            ['g_id' => $headBatch['g_id']],
            'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,sell_by_date'
        );
        $goods = $goods ? (is_object($goods) ? $goods->toArray() : $goods) : [];

        $machineGoods = MachineGoodsModel::getFind(
            ['m_id' => $this->machine['m_id'], 'g_id' => $headBatch['g_id']],
            'mg_id,intergral_rate,gift_points'
        );
        $machineGoods = $machineGoods ? (is_object($machineGoods) ? $machineGoods->toArray() : $machineGoods) : [];

        return $this->updateMachineChannel([
            'mc_id' => $mcId,
            'mg_id' => $machineGoods['mg_id'] ?? 0,
            'g_id' => $headBatch['g_id'],
            'g_name' => $goods['g_name'] ?? '',
            'gc_id' => $goods['gc_id'] ?? 0,
            'gc_name' => $goods['gc_name'] ?? '',
            'pic' => $goods['pic'] ?? '',
            'sku' => $goods['sku'] ?? '',
            'bar_code' => $goods['bar_code'] ?? '',
            'cost_price' => $goods['cost_price'] ?? 0,
            'market_price' => $goods['market_price'] ?? 0,
            'retail_price' => $headBatch['retail_price'] ?? ($goods['retail_price'] ?? 0),
            'gift_points' => $headBatch['gift_points'] ?? ($machineGoods['gift_points'] ?? 0),
            'cost_points' => $headBatch['cost_points'] ?? 0,
            'intergral_rate' => $machineGoods['intergral_rate'] ?? 0,
            'stock_warning' => max(0, intval($headBatch['stock_warning'] ?? 0)),
            'capacity' => $headBatch['capacity'] ?? 0,
            'stock' => $headBatch['stock'] ?? 0,
            'frozen_stock' => $headBatch['frozen_stock'] ?? 0,
            'batch_number' => $headBatch['batch_number'] ?? '',
            'manufacture_time' => $headBatch['manufacture_time'] ?? 0,
            'expire_time' => $headBatch['expire_time'] ?? 0,
            'sell_by_date' => $headBatch['sell_by_date'] ?? ($goods['sell_by_date'] ?? 0),
            'status' => (int)($headBatch['stock'] ?? 0) > 0 ? 1 : 3,
            'is_multi_goods' => 1,
        ]);
    }
    // ==================== 单货道多商品相关结束 ====================

    /**
     * 整理补货数据
     * @param array $mc
     * @param int|float|string $quantity
     * @param int $creator
     * @return array
     */
    protected function handleRepData($mc, $quantity)
    {
        // ensure numeric/string types for bcadd
        $before = isset($mc['stock']) ? $mc['stock'] : 0;
        // bcadd expects string inputs; cast to string to avoid warnings
        $after = bcadd((string)$before, (string)$quantity);

        $repData = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "mc_id" => $mc['mc_id'],
            "channel_code" => $mc['channel_code'],
            "mg_id" => $mc['mg_id'],
            "g_id" => $mc['g_id'],
            "g_name" => $mc['g_name'],
            "gc_id" => $mc['gc_id'],
            "gc_name" => $mc['gc_name'],
            "pic" => $mc['pic'],
            "sku" => $mc['sku'],
            "bar_code" => $mc['bar_code'],
            "batch_number" => $mc['batch_number'],
            "before" => $before,
            "quantity" => $quantity,
            "after" => $after,
            "creator" => $this->data['operator'] ?? 0
        ];
        return $repData;
    }

}