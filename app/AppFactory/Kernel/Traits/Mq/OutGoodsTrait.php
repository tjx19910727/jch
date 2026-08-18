<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/16
 * Time: 15:53
 */

namespace app\AppFactory\Kernel\Traits\Mq;

use app\AppFactory\Kernel\Traits\Api\ApiOutStatusNotifyTrait;
use think\facade\Db;


trait OutGoodsTrait
{
    use ApiOutStatusNotifyTrait;

    /**
     * 出货处理
     * @return int
     */
    public function outGoods()
    {
        $tradeNo = trim((string)($this->message['trade_no'] ?? ''));
        if ($tradeNo === '') {
            actionLog($this->message, 'trade_no为空，拒绝处理', 'OutGoods');
            return $this->rFail("trade_no不能为空");
        }

        Db::startTrans();
        try {
            actionLog($this->message,'出货完成','OutGoods');
            // 使用行锁保证同一trade_no并发时串行处理
            $this->order = Db::name('sale_orders')->where(['trade_no' => $tradeNo])->lock(true)->find();
            if (!$this->order) {
                actionLog($this->getLS(),'查无订单数据','OutGoods');
                Db::rollback();
                return $this->rFail("查无订单数据");
            }

            $originOutStatus = (int)$this->order['out_status'];

            $status = isset($this->message['status']) ? (int)$this->message['status'] : 0;
            $statusMap = [
                1 => 2,
                2 => 3,
                20 => 3,
                21 => 3,
                3 => 4,
                4 => 5,
            ];
            if ($status && isset($statusMap[$status]) && $this->order['out_status'] != 6) {
                // 防止状态回退
                if ($statusMap[$status] >= (int)$this->order['out_status']) {
                    $this->order['out_status'] = $statusMap[$status];
                }
                if (in_array($status, [3, 4], true)) {
                    $this->order['out_time'] = time();
                }
                $this->order['remark'] = "接收到出货状态上报,status=" . $status;
            }

            // 幂等短路：已处理过的结果回调直接成功返回，避免重复扣减/回调
            if ((!empty($this->message['main']) || in_array($status, [21, 3, 4])) 
                && $originOutStatus >= 4 && $originOutStatus != 6) {
                actionLog($this->order, '订单已处理过，本次按幂等成功返回', 'OutGoods');
                Db::commit();
                return $this->rAction(true);
            }

            // status=1/2/20 仅更新订单状态，不触发出货结果处理
            if (in_array($status, [1, 2, 20], true)) {
                $result = $this->updateSaleOrders($this->order);
                actionLog($this->order, '收到状态回执，更新订单出货状态', 'OutGoods');
                actionLog($this->getLS(), '【SQL】修改订单(状态回执)', 'OutGoods');
                Db::commit();
                return $this->rAction($result);
            }

            // 仅状态回执（无main）：status=21 仅更新到“处理中”；status=3/4 需带main，否则拒绝完结
            if (empty($this->message['main']) && $status === 21) {
                $result = $this->updateSaleOrders($this->order);
                actionLog($this->order, '仅状态回执，更新订单出货状态', 'OutGoods');
                actionLog($this->getLS(), '【SQL】修改订单(仅状态回执)', 'OutGoods');
                Db::commit();
                return $this->rAction($result);
            }

            if (empty($this->message['main']) && in_array($status, [3, 4], true)) {
                actionLog($this->message, 'status=3/4缺少main主体数据，拒绝完结', 'OutGoods');
                Db::rollback();
                return $this->rFail("主体数据不能为空");
            }

            if ($originOutStatus >= 4 && $originOutStatus != 6) {
                actionLog($this->order,'订单已处理过了(幂等)','OutGoods');
                Db::commit();
                return $this->rAction(true);
            }
            if (empty($this->message['main'])) {
                actionLog($this->message, '缺少main主体数据', 'OutGoods');
                Db::rollback();
                return $this->rFail("主体数据不能为空");
            }

            // 处理修改订单及货道数据
            $flag = $this->handleData();
            if ($this->order['coupon_id']) {
                $this->handleCoupon();
            }
            if ($this->order['apc_id']) {
                $this->handlePick();
            }
            if ($this->order['lottery_id']) {
                $this->handleLottery();
            }
            if ($this->order['fd_id']) {
                $this->handleFd();
            }
            $result = $this->checkFlag($flag);
            if ($result) {
                $this->handleTripPayCallback();
                $this->handleOrderOutStatusCallback();
                Db::commit();
            } else {
                Db::rollback();
            }
            return $this->rAction($result);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e,1,'OutGoods');
            return $this->rTryCatch($e->getMessage());
        }

    }

    /**
     * 修改订单、副表、货道
     * @return array
     */
    protected function handleData()
    {
        $flag = [];
        $status = isset($this->message['status']) ? (int)$this->message['status'] : 0;
        if ($this->order['out_status'] != 6) {
            if ($status == 4) {
                $this->order['out_status'] = 5;
            } else {
                $this->order['out_status'] = 4;
            }
        }
        if ($status != 21) {
            $this->order['out_time'] = time();
        }
        $this->order['remark'] = $status == 21 ? "接收到出货结果并扣减库存,status=21" : "接收到出货结果";

        $insertGChange = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "ao_id" => $this->machine['ao_id'],
        ];

        foreach ($this->message['main'] as $key => $value) {
            $position = $key;
            foreach ($value as $vv) {
                $channel_code = $vv["channel_code"] ?? '';
                $success = intval($vv["success_quantity"] ?? 0);
                $fail = intval($vv["fail_quantity"] ?? 0);
                $deliver_pics = $vv["deliver_pics"] ?? "";
                $out_sequence = $vv["out_sequence"] ?? 1;

                $where = [];
                $whereMc = [];
                $whereUpdateSod = [];
                // 修改订单副表
                $where['order_id'] = $this->order['order_id'];
                $where['channel_position'] = $position;
                $where['channel_code'] = $channel_code;
                $where['success_quantity'] = 0;
                $where['fail_quantity'] = 0;
                $sod = $this->getSaleOrdersDetailsFind($where,'sod_id','sod_id asc');
                if (!$sod) continue;
                if ($sod) {
                    // ★★ 关键幂等保护：子订单已有出货结果（成功/失败），说明该货道已处理过，
                    // 本次重复回执（MQ重投/HTTP+MQ双通道）不能再更新子订单、扣减库存、生成变化日志。
                    // 防止设备重复出货/回执时库存被重复扣减。
                    $existing = Db::name('sale_orders_details')
                        ->where('sod_id', intval($sod['sod_id']))
                        ->whereRaw('success_quantity > 0 OR fail_quantity > 0')
                        ->find();
                    if ($existing) {
                        actionLog([
                            'sod_id' => intval($sod['sod_id']),
                            'order_id' => $this->order['order_id'],
                            'channel_code' => $channel_code,
                            'position' => $position,
                            'success_quantity' => intval($existing['success_quantity'] ?? 0),
                            'fail_quantity' => intval($existing['fail_quantity'] ?? 0),
                        ], 'OutGoods子订单已处理，跳过重复扣库存/更新', 'OutGoods');
                        continue;
                    }

                    unset($where);
                    $update = [];
                    $whereUpdateSod['sod_id'] = $sod['sod_id'];
                    $update['success_quantity'] = $success;
                    $update['fail_quantity'] = $fail;
                    $update['deliver_pics'] = $deliver_pics;
                    $update['out_sequence'] = $out_sequence;
                    actionLog($update, '修改订单副表参数', 'OutGoods');
                    $flag[] = $this->updateSaleOrdersDetails($update, $whereUpdateSod, ['success_quantity', "fail_quantity", 'deliver_pics', 'out_sequence']);
                    actionLog($this->getLS(), '【SQL】修改订单副表', 'OutGoods');
                }
                // 修改货道
                $updateMc = [];
                $whereMc['channel_code'] = $channel_code;
                $whereMc['m_id'] = $this->machine['m_id'];
                $whereMc['channel_position'] = $position;
                $mc = $this->getMachineChannelFind($whereMc,'mc_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,frozen_stock,stock,out_fail_stock,stock_warning');
                if (!$mc) {
                    actionLog($whereMc, '未找到货道，跳过货道库存处理', 'OutGoods');
                    continue;
                }
                if ($success > 0 && (in_array($status, [21, 3]) || $this->message['main'])) {
                    // 外部预订提货码订单，减冻结库存
                    if ($this->order['apc_id'] && $this->getActivityPickCodeValue(['order_id' => $this->order['order_id']],'pick_type') == 3) {
                        $updateMc['frozen_stock'] = bcsub($mc['frozen_stock'],$success);
                        $stock = $mc['stock'];
                    } else {
                        $updateMc['stock'] = bcsub($mc['stock'], $success);
                        $stock = $updateMc['stock'];
                    }
                    // 库存达到货道库存预警值
                    actionLog($mc,"货道数据",'OutGoods');
                    actionLog(['stock' => $stock,'frozen_stock' => $updateMc['frozen_stock'] ?? $mc['frozen_stock']],'库存值','OutGoods');
                    if (!$mc['stock_warning']) {
                        $machineConfig = $this->getMachineConfigFind(['m_id' => $this->machine['m_id']],'stock_warning');
                        if ($machineConfig['stock_warning'] > 0 ) $mc['stock_warning'] = $machineConfig['stock_warning'];
                    }
                    // 发送补货通知
                    if ($stock <= $mc['stock_warning']) {
                        try {
                            $errorCode = "1000101";
                            $this->noticeSendData = [
                                "ao_id" => $this->machine['ao_id'],
                                "m_id" => $this->machine['m_id'],
                                "templateType" => "understock",
                                "replaceData" => [
                                    "machine_id" => $this->machine['machine_id'],
                                    "machine_name" => $this->machine['machine_name'],
                                    "stock" => $stock,
                                    "channel_code" => $mc['channel_code'],
                                    "stock_warning" => $mc['stock_warning'] ?? 0,
                                    "error_code" => $this->lang("deviceErrorCode.".$errorCode),
                                    "error_time" => date('Y-m-d H:i:s'),
                                    "error_info" => $mc['channel_code'],
                                ]
                            ];
                            actionLog($this->noticeSendData,'发送补货通知','OutGoods');
                            $result = $this->noticeSend();
                            actionLog($result, '发送补货通知结果','OutGoods');
                        } catch (\Exception $e) {
                            actionLog("发送补货通知抛出异常","",'OutGoods');
                            actionException($e, 1);
                        }
                    }

                    // 销售出货成功后再生成商品变化数据
                    $insertGc = array_merge($insertGChange,[
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
                        "change_value" => $success,
                    ]);
                    $insertGc['desc'] = $this->lang("goodsChange.terminal_sale_dec_stock");
                    $insertGc['position'] = 1;
                    $insertGc['type'] = 3;
                    $this->addGoodsChange($insertGc);
                    actionLog($this->getLS(),'【SQL】添加商品变化数据','OutGoods');
                }
                if ($fail > 0) {
//                    $updateMc['status'] = 3;
                    $this->order['out_status'] == 6 ? : $this->order['out_status'] = 5;
                    $currentStock = isset($updateMc['stock']) ? intval($updateMc['stock']) : intval($mc['stock']);
                    $updateMc['stock'] = max(0, $currentStock - $fail);
                    $updateMc['out_fail_stock'] = max(0, intval($mc['out_fail_stock'] ?? 0)) + $fail;

                    // 出货失败发送通知
                    try {
                        $this->noticeSendData = [
                            "ao_id" => $this->machine['ao_id'],
                            "m_id" => $this->machine['m_id'],
                            "templateType" => "tException",
                            "replaceData" => [
                                "machine_id" => $this->machine['machine_id'],
                                "machine_name" => $this->machine['machine_name'],
                                "now" => date('Y-m-d H:i:s'),
                                "error_info" => $this->lang("tException.out_fail"),
                                "error_code" => $channel_code,
                                "exceptionDeclaration" => $channel_code . $this->lang("tException.out_fail"),
                            ]
                        ];
                        actionLog($this->noticeSendData,'发送出货失败通知');
                        $result = @$this->noticeSend();
                        actionLog($result, '发送出货失败通知结果');
                    } catch (\Exception $e) {
                        actionLog("发送出货失败抛出异常");
                        actionException($e, 1);
                    }
                }
                if ($updateMc) {
                    $updateMc['mc_id'] = $mc['mc_id'];
                    $flag[] = $this->updateMachineChannel($updateMc);
                    actionLog($this->getLS(),'【SQL】修改设备货道','OutGoods');
                }
            }
        }
        // 修改订单
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(),'【SQL】修改订单','OutGoods');
        actionLog($flag,'处理出货结果','OutGoods');
        return $flag;
    }
}