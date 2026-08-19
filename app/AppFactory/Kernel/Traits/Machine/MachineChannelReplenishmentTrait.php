<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/1
 * Time: 18:02
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Goods\GoodsChangeModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelReplenishmentModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VChannelReplenishment;

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
                ];
            }
            $repMap[$mcId]['quantity'] += (int)($value['quantity'] ?? 0);
            $repMap[$mcId]['standby_quantity'] += (int)($value['standby_quantity'] ?? 0);
        }

        if (!$repMap) {
            return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
        }

        $flag = [];
        $goodsChangeRows = [];
        $repRows = [];
        $this->startTrans();
        try {
            $recordNo = trim(strval($this->data['record_no'] ?? ''));
            $preOrder = null;
            if ($recordNo !== '') {
                $preOrder = PreReplenishmentOrderModel::where(['record_no' => $recordNo])->lock(true)->find();
                if (!$preOrder) {
                    $this->rollbackTrans();
                    return $this->rFail('预补货单不存在');
                }
                $confirmed = PreReplenishmentDetailModel::where([
                    ['order_id', '=', $preOrder['id']],
                    ['machine_id', '=', $this->machine['machine_id']],
                    ['order_count', '>=', 1],
                ])->count();
                if ($confirmed) {
                    $this->rollbackTrans();
                    return $this->rFail('该设备已完成预补货，请勿重复上报');
                }
            }
            $mcIds = array_keys($repMap);
            $mcField = 'mc_id,m_id,channel_code,capacity,stock,frozen_stock,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number';
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

                if ($recordNo) {
                    $syncQuantity = $value['quantity'];
                    if (isset($value['standby_quantity'])) {
                        $syncQuantity += $value['standby_quantity'];
                    }
                    if ($syncQuantity != 0) {
                        $flag[] = $this->syncByTerminalReplenishmentRecordNo($recordNo, $mc, $syncQuantity, $this->data);
                        $flag[] = PreReplenishmentDetailModel::where([
                            'order_id' => $preOrder['id'],
                            'machine_id' => $this->machine['machine_id'],
                            'channel_code' => $mc['channel_code'],
                            'sku' => $mc['sku'],
                        ])->update(['order_count' => 1]);
                    }
                }

                $flag[] = $this->updateMachineChannel(['mc_id' => $mc['mc_id'], 'stock' => $mc['stock']]);
                $mcMap[$mc['mc_id']]['stock'] = $mc['stock'];
            }

            if ($goodsChangeRows) {
                foreach (array_chunk($goodsChangeRows, 500) as $batch) {
                    $flag[] = GoodsChangeModel::insertAll($batch);
                }
            }

            if ($repRows) {
                foreach (array_chunk($repRows, 500) as $batch) {
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
