<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Mall\MallMachineModel;
use app\AppFactory\Kernel\Model\Mall\MallModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VChannel;
use app\AppFactory\Kernel\Traits\Mall\MallMachineTrait;
use think\facade\Db;

trait MachineChannelTrait
{
    use MallMachineTrait;

    /**
     * 增加指定字段数值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
    public function setMachineChannelInc($where, $field, $inc = 1)
    {
        return MachineChannelModel::setInc($where, $field, $inc);
    }

    /**
     * 减少指定字段数值
     * @param $where
     * @param $field
     * @param int $dec
     * @return mixed
     */
    public function setMachineChannelDec($where, $field, $dec = 1)
    {
        return MachineChannelModel::setDec($where, $field, $dec);
    }

    public function getMachineChannelCount($where)
    {
        return MachineChannelModel::getCount($where);
    }

    public function getMachineChannelCountV2($where)
    {
        $whereRaw = "(a.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
        if (isset($where['raw'])) {
            $whereRaw .= " AND " . $where['raw'];
            unset($where['raw']);
        }
        return MachineChannelModel::alias("a")->where($where)->whereRaw($whereRaw)->count();
    }

    public function getMachineChannelColumnV2($where, $column, $key = "")
    {
        $whereRaw = "EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1)";
        if (isset($where['raw'])) {
            $whereRaw .= " AND " . $where['raw'];
            unset($where['raw']);
        }
        return MachineChannelModel::alias("a")->where($where)->whereRaw($whereRaw)->column($column, $key);
    }

    public function getMachineChannelSum($where, $sum)
    {
        return MachineChannelModel::getSum($where, $sum);
    }

    public function getMachineChannelValue($where, $value)
    {
        return MachineChannelModel::getFieldValue($where, $value);
    }

    public function getMachineChannelColumn($where, $column, $key = "")
    {
        return MachineChannelModel::getColumn($where, $column, $key);
    }

    /**
     * 查询一条货道
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineChannelFind($where, $field = "*", $order = "")
    {
        return MachineChannelModel::getFind($where, $field, $order);
    }

    public function getMachineChannelList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = '')
    {
        return MachineChannelModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    /**
     * 获取货道关联的自由组合商品表
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineChannelModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachineChannelJoinMfgList($where, $field = "*", $order = "")
    {
        return MachineChannelModel::joinMfgList($where, $field, $order);
    }

    public function getMachineChannelJoinGoodsList($where, $field = "*", $order = "", $group = "")
    {
        return MachineChannelModel::joinGoodsList($where, $field, $order, $group);
    }

    public function addMachineChannel($insert)
    {
        $data = MachineChannelModel::create($insert);
        return $data->mc_id;
    }

    public function addMachineMoreChannel($insert)
    {
        $mc = new MachineChannelModel();
        return $mc->saveAll($insert);
    }

    public function updateMachineChannel($update, $where = [], $field = [])
    {
        return MachineChannelModel::update($update, $where, $field);
    }

    /**
     * BAD恢复时将出货失败库存回补到货道库存。
     * 出货失败库存表示已从可售库存扣减、但商品仍在设备内的数量。
     */
    protected function mergeOutFailStockOnBadRecover($mc, $update)
    {
        $outFailStock = max(0, intval($mc['out_fail_stock'] ?? 0));
        if ($outFailStock <= 0) {
            return $update;
        }

        $baseStock = array_key_exists('stock', $update)
            ? intval($update['stock'])
            : intval($mc['stock'] ?? 0);
        $update['stock'] = $baseStock + $outFailStock;
        $update['out_fail_stock'] = 0;
        return $update;
    }

    public function delMachineChannel($where)
    {
        $result = MachineChannelModel::whereDel($where);
        return $result;
    }

    /**
     * 终端上报设备货道
     * http://sd.dakemakeji.com/web/#/78?page_id=2209
     * @return array
     */
    public function terminalSubChannel()
    {
        $channelList = [];
        $flag = [];
        $this->startTrans();
        try {
            if (isset($this->data['delList']) && $this->data['delList']) {
                $flag[] = $this->delMachineChannel([['mc_id', 'in', $this->data['delList']]]);
            }
            if (isset($this->data['mcList'])) {
                $mcList = json2arr($this->data['mcList']);
                foreach ($mcList as $key => $value) {
                    $whereMc = [];
                    try {
                        validate(VChannel::class)->scene("subChannel")->check($value);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($this->lang($e->getMessage()));
                    }
                    $value['m_id'] = $this->machine['m_id'];
                    $value['machine_id'] = $this->machine['machine_id'];
                    if (isset($value['mc_id']) && $value['mc_id']) {
                        $whereMc['mc_id'] = $value['mc_id'];
                    } else {
                        $whereMc['m_id'] = $this->machine['m_id'];
                        $whereMc['channel_code'] = $value['channel_code'];
                        $whereMc['channel_position'] = $value['channel_position'];
                    }

                    $mc = $this->getMachineChannelFind($whereMc);
                    actionLog($this->getLS(), '查询货道');
                    if (!$mc) {
                        $mc = $value;
                        if (isset($value['g_id'])) {
                            $gField = "g_id,g_name,gc_id,gc_name,bar_code,sku,pic,cost_price,market_price,retail_price,ao_id";
                            $g = $this->getGoodsFind(['g_id' => $value['g_id']], $gField);
                            if ($g) {
                                $g = obj2arr($g);
                                $g['pic'] = str_replace($this->host, '', $g['pic']);
                                $g['mg_id'] = ($this->getMachineGoodsValue(['g_id' => $g['g_id'], 'm_id' => $this->machine['m_id']], 'mg_id') ?? 0);
                                $mc = array_merge($mc, $g);
                            }
                        }
                        $mc['mc_id'] = $this->addMachineChannel($mc);
                        if (!$mc['mc_id']) {
                            $this->rollbackTrans();
                            return $this->rFail($this->lang("VChannel.add_channel_fail") . ":" . $mc['channel_code']);
                        }
                        // ==================== 单货道多商品相关开始 ====================
                        // 多商品批次处理（设备上报固定队首模式）
                        $isMultiGoods = isset($value['is_multi_goods']) && intval($value['is_multi_goods']) === 1;
                        if ($isMultiGoods) {
                            $headBatch = $this->saveChannelGoodsBatch($mc['mc_id'], $value, $value['batch_arr'] ?? [], false);
                            if (!$headBatch) {
                                $value['is_multi_goods'] = 2;
                                $mc['is_multi_goods'] = 2;
                            } else {
                                $value['g_id']          = $headBatch['g_id'];
                                $value['stock']         = $headBatch['stock'];
                                $value['frozen_stock']  = $headBatch['frozen_stock'];
                                $value['retail_price']  = $headBatch['retail_price'];
                                $value['gift_points']   = $headBatch['gift_points'];
                                $value['is_multi_goods'] = 1;
                                $mc = array_merge($mc, [
                                    'g_id'          => $headBatch['g_id'],
                                    'stock'         => $headBatch['stock'],
                                    'frozen_stock'  => $headBatch['frozen_stock'],
                                    'retail_price'  => $headBatch['retail_price'],
                                    'gift_points'   => $headBatch['gift_points'],
                                    'is_multi_goods' => 1,
                                ]);
                            }
                        }
                        // ==================== 单货道多商品相关结束 ====================
                        
                        // 20250604 新增货道，增加“上货”商品变化记录
                        $insertGChange = [
                            "m_id" => $this->machine['m_id'],
                            "machine_id" => $this->machine['machine_id'],
                            "machine_name" => $this->machine['machine_name'],
                            "mc_id" => $mc['mc_id'],
                            "channel_code" => $mc['channel_code'],
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $mc['g_id'] ?? 0,
                            "g_name" => $mc['g_name'] ?? "",
                            "gc_id" => $mc['gc_id'] ?? 0,
                            "gc_name" => $mc['gc_name'] ?? "",
                            "pic" => $mc['pic'] ?? "",
                            "sku" => $mc['sku'] ?? "",
                            "bar_code" => $mc['bar_code'] ?? "",
                            "ao_id" => $this->machine['ao_id'],
                            "change_value" => $value['stock'] ?? 0,
                            "type" => 2 ,   // 2：创建上货
//                            "desc" => $this->lang("goodsChange.terminal_create_inc_stock"),
                            "position" => 1,
                        ];
                        $this->addGoodsChange($insertGChange);
                    } else {
                        $mc = $mc->toArray() ?? obj2arr($mc);

                        // ==================== 单货道多商品相关开始 ====================
                        // 多商品批次处理（设备上报固定队首模式）
                        $isMultiGoods = isset($value['is_multi_goods']) && intval($value['is_multi_goods']) === 1;
                        if ($isMultiGoods) {
                            $headBatch = $this->saveChannelGoodsBatch($mc['mc_id'], $value, $value['batch_arr'] ?? [], false);
                            if (!$headBatch) {
                                $value['is_multi_goods'] = 2;
                                $mc['is_multi_goods'] = 2;
                            } else {
                                $value['g_id']          = $headBatch['g_id'];
                                $value['stock']         = $headBatch['stock'];
                                $value['frozen_stock']  = $headBatch['frozen_stock'];
                                $value['retail_price']  = $headBatch['retail_price'];
                                $value['gift_points']   = $headBatch['gift_points'];
                                $value['is_multi_goods'] = 1;
                                $mc = array_merge($mc, [
                                    'g_id'          => $headBatch['g_id'],
                                    'stock'         => $headBatch['stock'],
                                    'frozen_stock'  => $headBatch['frozen_stock'],
                                    'retail_price'  => $headBatch['retail_price'],
                                    'gift_points'   => $headBatch['gift_points'],
                                    'is_multi_goods' => 1,
                                ]);
                            }
                        }
                        // ==================== 单货道多商品相关结束 ====================

                        $insertGChange = [
                            "m_id" => $this->machine['m_id'],
                            "machine_id" => $this->machine['machine_id'],
                            "machine_name" => $this->machine['machine_name'],
                            "mc_id" => $mc['mc_id'],
                            "channel_code" => $mc['channel_code'],
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $mc['g_id'] ?? 0,
                            "g_name" => $mc['g_name'] ?? "",
                            "gc_id" => $mc['gc_id'] ?? 0,
                            "gc_name" => $mc['gc_name'] ?? "",
                            "pic" => $mc['pic'] ?? "",
                            "sku" => $mc['sku'] ?? "",
                            "bar_code" => $mc['bar_code'] ?? "",
                            "ao_id" => $this->machine['ao_id'],
                            "change_value" => $value['stock'] ?? 0,
                            "position" => 1,
                        ];
                        if (isset($value['status']) && intval($value['status']) === 1 && intval($mc['status']) === 3) {
                            $value = $this->mergeOutFailStockOnBadRecover($mc, $value);
                        }
                        // 20250604 检查货道库存上货或下货，变化数量 = 上报stock - 当前货道stock
                        if (isset($value['stock']) && $value['stock'] != $mc['stock']) {
                            $changeValue = bcsub($value['stock'],$mc['stock']);
                            $insertGc = array_merge($insertGChange, [
                                "change_value" => $changeValue,
                                "type" => $changeValue > 0 ? 2 : 3 ,    // >0：上货，<0 下货
                                "desc" => $changeValue > 0 ? $this->lang("goodsChange.terminal_rep_inc_mc_stock") : $this->lang("goodsChange.terminal_rep_dec_mc_stock"),
                            ]);
                            $this->addGoodsChange($insertGc);
                        }
                        // 20250604 检查Bad状态，终端BAD 10 或终端恢复BAD 11
                        if (isset($value['status']) && $value['status'] != $mc['status'] && in_array($value['status'], [1, 3])) {
                            $insertGc = array_merge($insertGChange, [
                                "change_value" => $mc['stock'],
                                "type" => $value['status'] == 3 ? 10 : 11 ,   // 3：终端BAD，1：终端恢复BAD
                                "desc" => $value['status'] == 3 ? $this->lang("goodsChange.terminal_mc_bad") : $this->lang("goodsChange.terminal_mc_not_bad"),
                            ]);
                            $this->addGoodsChange($insertGc);
                        }

                        $mc = array_merge($mc, $value);
                        $mc = $this->updateMachineChannel($mc);
                        if (!$mc) {
                            $this->rollbackTrans();
                            return $this->rFail($this->lang("VChannel.update_channel_fail") . ":" . $mc['channel_code']);
                        }
                    }
                    $channelList[] = $mc;
                }
            }
            return $this->checkTrans($this->checkFlag($flag));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 货道槽位照片上传
     * @return MachineChannelTrait
     */
    public function channelImg()
    {
        $result = $this->updateMachineChannel(['channel_img' => $this->message['path']], ['m_id' => $this->machine['m_id'], 'channel_code' => $this->message['channel_code']]);
        actionLog($this->getLS(), '【SQL】保存货道槽位照片', 'DataUpload');
        return $result;
    }


    public function getRateOrGiftPoints($mc)
    {
        // Safe handling: guard against null/invalid $mc. Return explicit defaults when missing.
        if (empty($mc)) {
            return ['intergral_rate' => 0, 'gift_points' => 0];
        }

        // Normalize $mc to array for safe access if it's an object
        $mcArr = $mc;
        if (is_object($mc)) {
            // obj2arr helper exists elsewhere in codebase
            $mcArr = function_exists('obj2arr') ? obj2arr($mc) : (array)$mc;
        }

        // If device-channel level config exists, use it
        $ir = $mcArr['intergral_rate'] ?? null;
        $gp = $mcArr['gift_points'] ?? null;
        if ($ir !== null || $gp !== null) {
            return [
                'intergral_rate' => $ir ?: 0,
                'gift_points' => $gp ?: 0,
            ];
        }

        // Fallback to machine-goods level (requires g_id and machine_id)
        $g_id = $mcArr['g_id'] ?? null;
        $machine_id = $mcArr['machine_id'] ?? null;
        if ($g_id && $machine_id) {
            $machineGoodsInfo = MachineGoodsModel::getFind(['g_id' => $g_id, 'machine_id' => $machine_id], 'intergral_rate,gift_points');
            if ($machineGoodsInfo) {
                return [
                    'intergral_rate' => $machineGoodsInfo['intergral_rate'] ?? 0,
                    'gift_points' => $machineGoodsInfo['gift_points'] ?? 0,
                ];
            }

            $goodsInfo = GoodsModel::getFind(['g_id' => $g_id], 'intergral_rate,gift_points');
            if ($goodsInfo) {
                return [
                    'intergral_rate' => $goodsInfo['intergral_rate'] ?? 0,
                    'gift_points' => $goodsInfo['gift_points'] ?? 0,
                ];
            }

            $machineInfo = MachineModel::getFind(['machine_id' => $machine_id]);
            if ($machineInfo) {
                $mallMachine = $this->getMallMachineFind(['machine_id' => $machine_id]);
                if ($mallMachine && !empty($mallMachine['m_id'])) {
                    $mall = MallModel::getFind(['mall_id' => $mallMachine['m_id']], 'intergral_rate');
                    if ($mall && !empty($mall['intergral_rate'])) {
                        return ['intergral_rate' => $mall['intergral_rate'], 'gift_points' => 0];
                    }
                }
            }
        }

        return ['intergral_rate' => 0, 'gift_points' => 0];
    }

    // ==================== 多商品批次相关 ====================
    /**
     * 保存货道批次商品队列（全量覆盖）
     * @param int    $mc_id       货道ID
     * @param array  $headData    队首数据 {g_id, stock, capacity, retail_price, gift_points, manufacture_time, batch_number}
     * @param array  $batchArr    后续商品数组 [{g_id, stock, ...}, ...]
     * @param bool   $autoReorder 是否自动重排（true: 找第一个有库存的作为队首; false: 以传入顺序为准）
     * @return array|null         返回队首批次信息，失败返回 null
     */
    public function saveChannelGoodsBatch($mc_id, $headData, $batchArr, $autoReorder = true)
    {
        // 0. 校验货道是否存在
        $mc = $this->getMachineChannelFind(['mc_id' => $mc_id], 'mc_id');
        if (!$mc) {
            return null;
        }

        // 1. 全量取消旧队列（售卖中、等待、结束都取消）
        Db::name('channel_goods_batch')->where('mc_id', $mc_id)
             ->whereIn('status', [1, 2, 3])
             ->update(['status' => 4]);

        // 2. 构建完整批次列表：队首 + 后续
        $allBatches = array_merge([$headData], $batchArr);
        $rows = [];
        $headBatch = [];
        $headIndex = -1;

        if ($autoReorder) {
            // 自动模式：找第一个有库存的作为队首
            $foundHead = false;
            foreach ($allBatches as $i => $item) {
                $stock = intval($item['stock'] ?? 0);
                if (!$foundHead && $stock > 0) {
                    $status = 1;
                    $foundHead = true;
                    $headIndex = $i;
                } else {
                    $status = $stock == 0 ? 3 : 2;
                }
                $rows[] = $this->buildBatchRow($mc_id, $item, $status);
            }
            // 重排：有效队首放到第1位
            if ($headIndex > 0) {
                $beforeHead = array_slice($rows, 0, $headIndex);
                $fromHeadOn = array_slice($rows, $headIndex);
                $rows = array_merge($fromHeadOn, $beforeHead);
            }
        } else {
            // 固定模式：以传入顺序为准，第一个即队首
            foreach ($allBatches as $i => $item) {
                $stock = intval($item['stock'] ?? 0);
                $status = $i === 0 ? 1 : ($stock == 0 ? 3 : 2);
                $rows[] = $this->buildBatchRow($mc_id, $item, $status);
            }
            $headBatch = $rows[0];
        }

        // 3. 重新编号 sequence
        $insertData = [];
        foreach ($rows as $idx => $row) {
            $row['sequence'] = $idx + 1;
            $insertData[] = $row;
            if ($autoReorder && $row['status'] === 1 && !$headBatch) {
                $headBatch = $row;
            }
        }

        // 4. 批量插入
        try {
            Db::name('channel_goods_batch')->insertAll($insertData);
        } catch (\Exception $e) {
            actionException($e, 1, 'saveChannelGoodsBatch');
            return null;
        }

        return $headBatch;
    }

    /**
     * 构建单个批次行数据
     */
    private function buildBatchRow($mc_id, $item, $status)
    {
        return [
            'mc_id'            => $mc_id,
            'g_id'             => intval($item['g_id'] ?? 0),
            'sequence'         => 0,
            'capacity'         => intval($item['capacity'] ?? ($item['stock'] ?? 0)),
            'stock'            => intval($item['stock'] ?? 0),
            'frozen_stock'     => 0,
            'sold_quantity'    => 0,
            'retail_price'     => $item['retail_price'] ?? 0,
            'gift_points'      => $item['gift_points'] ?? 0,
            'manufacture_time' => $item['manufacture_time'] ?? 0,
            'expire_time'      => $item['expire_time'] ?? 0,
            'sell_by_date'     => $item['sell_by_date'] ?? 0,
            'batch_number'     => $item['batch_number'] ?? '',
            'status'           => $status,
        ];
    }

    /**
     * 出货后尝试切换到下一个批次（多商品 FIFO）
     * @param int $mc_id 货道ID
     */
    public function trySwitchNextBatch($mc_id)
    {
        $mc = $this->getMachineChannelFind(['mc_id' => $mc_id], 'mc_id,is_multi_goods,stock,frozen_stock,m_id,machine_id');
        if (!$mc) {
            return;
        }
        $mc = is_object($mc) ? $mc->toArray() : $mc;

        // 非多商品模式或还有库存则跳过
        if (intval($mc['is_multi_goods'] ?? 2) !== 1) {
            return;
        }
        if (intval($mc['stock'] ?? 0) + intval($mc['frozen_stock'] ?? 0) > 0) {
            return;
        }

        // 当前队首批次标记为已结束
        Db::name('channel_goods_batch')
            ->where('mc_id', $mc_id)
            ->where('status', 1)
            ->update(['status' => 3]);

        // 查找下一个等待中的批次
        $nextBatch = Db::name('channel_goods_batch')
            ->where('mc_id', $mc_id)
            ->where('status', 2)
            ->order('sequence asc')
            ->find();

        if (!$nextBatch) {
            // 无下一批次，货道设为 BAD
            $this->updateMachineChannel(['status' => 3], ['mc_id' => $mc_id]);
            return;
        }

        // 切换到下一批次
        Db::name('channel_goods_batch')
            ->where('batch_id', $nextBatch['batch_id'])
            ->update(['status' => 1]);

        $goods = GoodsModel::getFind(
            ['g_id' => $nextBatch['g_id']],
            'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,sell_by_date'
        );
        $goods = $goods ? (is_object($goods) ? $goods->toArray() : $goods) : [];

        $machineGoods = MachineGoodsModel::getFind(
            ['m_id' => $mc['m_id'], 'g_id' => $nextBatch['g_id']],
            'mg_id,intergral_rate,gift_points'
        );
        $machineGoods = $machineGoods ? (is_object($machineGoods) ? $machineGoods->toArray() : $machineGoods) : [];

        $manufactureTime = intval($nextBatch['manufacture_time'] ?? 0);
        $sellByDate = intval($nextBatch['sell_by_date'] ?? ($goods['sell_by_date'] ?? 0));
        $expireTime = 0;
        if ($manufactureTime > 0 && $sellByDate > 0) {
            $expireTime = $manufactureTime + $sellByDate * 86400;
        }

        $updateMc = [
            'g_id'              => $nextBatch['g_id'],
            'mg_id'             => $machineGoods['mg_id'] ?? 0,
            'g_name'            => $goods['g_name'] ?? '',
            'gc_id'             => $goods['gc_id'] ?? 0,
            'gc_name'           => $goods['gc_name'] ?? '',
            'pic'               => $goods['pic'] ?? '',
            'sku'               => $goods['sku'] ?? '',
            'bar_code'          => $goods['bar_code'] ?? '',
            'cost_price'        => $goods['cost_price'] ?? 0,
            'market_price'      => $goods['market_price'] ?? 0,
            'stock'             => $nextBatch['stock'],
            'frozen_stock'      => $nextBatch['frozen_stock'],
            'retail_price'      => $nextBatch['retail_price'],
            'gift_points'       => $nextBatch['gift_points'] ?: ($machineGoods['gift_points'] ?? 0),
            'intergral_rate'    => $machineGoods['intergral_rate'] ?? 0,
            'batch_number'      => $nextBatch['batch_number'] ?? '',
            'manufacture_time'  => $manufactureTime,
            'expire_time'       => $expireTime,
            'sell_by_date'      => $sellByDate,
            'status'            => 1,
        ];
        $this->updateMachineChannel($updateMc, ['mc_id' => $mc_id]);

        // goods_change 记录切换
        if (method_exists($this, 'addGoodsChange')) {
            $this->addGoodsChange([
                "m_id"          => $mc['m_id'],
                "machine_id"    => $mc['machine_id'],
                "mc_id"         => $mc_id,
                "g_id"          => $nextBatch['g_id'],
                "change_value"  => $nextBatch['stock'],
                "type"          => 2,
                "desc"          => 'FIFO自动切换下一批次',
                "position"      => 1,
            ]);
        }

        // 通知设备更新货道
        if (!empty($mc['machine_id']) && method_exists($this, 'sendToMachine')) {
            $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => intval($mc_id)]);
        }
    }
}
