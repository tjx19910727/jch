<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/2
 * Time: 15:53
 */

namespace app\AppFactory\Kernel\Traits\Mq;

use app\AppFactory\Kernel\Traits\Api\ApiOutStatusNotifyTrait;
use think\facade\Db;


trait OutGoodsTrait
{
    use ApiOutStatusNotifyTrait;

    protected $outGoodsRefreshMcIds = [];

    /**
     * 出货处理
     * @return int
     */
    public function outGoods()
    {
        $this->outGoodsRefreshMcIds = [];
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
                foreach ($this->outGoodsRefreshMcIds as $mcId) {
                    try {
                        $this->sendToMachine(
                            ['machine_id' => $this->machine['machine_id']],
                            'updateMc',
                            ['mc_id' => $mcId]
                        );
                    } catch (\Throwable $e) {
                        actionException($e, 1, 'OutGoodsUpdateMc');
                    }
                }
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
                $sod = $this->getSaleOrdersDetailsFind($where,'sod_id,batch_id,quantity','sod_id asc');
                if (!$sod) continue;
                if ($sod) {
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
                        //单货道多商品开始
                        if (!empty($sod['batch_id'])) {
                            $batch = Db::name('channel_goods_batch')
                                ->where('batch_id', $sod['batch_id'])
                                ->field('batch_id,stock,frozen_stock,sold_quantity')
                                ->find();
                            if ($batch) {
                                $flag[] = Db::name('channel_goods_batch')
                                    ->where('batch_id', $sod['batch_id'])
                                    ->update([
                                        'frozen_stock' => $batch['frozen_stock'] > $success ? bcsub($batch['frozen_stock'], $success) : 0,
                                        'sold_quantity' => bcadd($batch['sold_quantity'], $success),
                                    ]);
                            }
                        }
                        //单货道多商品结束
                    } else {
                        $updateMc['stock'] = bcsub($mc['stock'], $success);
                        $stock = $updateMc['stock'];
                        //单货道多商品开始
                        if (!empty($sod['batch_id'])) {
                            $batch = Db::name('channel_goods_batch')
                                ->where('batch_id', $sod['batch_id'])
                                ->field('batch_id,stock,frozen_stock,sold_quantity')
                                ->find();
                            if ($batch) {
                                $flag[] = Db::name('channel_goods_batch')
                                    ->where('batch_id', $sod['batch_id'])
                                    ->update([
                                        'stock' => $batch['stock'] > $success ? bcsub($batch['stock'], $success) : 0,
                                        'sold_quantity' => bcadd($batch['sold_quantity'], $success),
                                    ]);
                            }
                        }
                        //单货道多商品结束
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
                    //单货道多商品开始
                    if (!empty($sod['batch_id'])) {
                        $batch = Db::name('channel_goods_batch')
                            ->where('batch_id', $sod['batch_id'])
                            ->field('batch_id,stock')
                            ->find();
                        if ($batch && intval($batch['stock']) > 0) {
                            $flag[] = Db::name('channel_goods_batch')
                                ->where('batch_id', $sod['batch_id'])
                                ->update([
                                    'stock' => max(0, intval($batch['stock']) - $fail),
                                ]);
                        }
                    }
                    //单货道多商品结束

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
                    $this->outGoodsRefreshMcIds[intval($mc['mc_id'])] = intval($mc['mc_id']);
                    actionLog($this->getLS(),'【SQL】修改设备货道','OutGoods');
                    // 多商品FIFO：出货后尝试切换下一批次
                    if (method_exists($this, 'trySwitchNextBatch')) {
                        $this->trySwitchNextBatch($mc['mc_id']);
                    }
                }
            }
        }
        // 修改订单
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(),'【SQL】修改订单','OutGoods');
        actionLog($flag,'处理出货结果','OutGoods');
        return $flag;
    }

    /**
     * 处理优惠券
     */
    protected function handleCoupon()
    {
        $couponId = intval($this->order['coupon_id'] ?? 0);
        if ($couponId <= 0) {
            return;
        }
        $used = $this->getActivityCouponUsedFind([
            'order_id' => $this->order['order_id'],
            'c_id' => $couponId,
        ],'c_id,code,code_type');
        if ($used) {
            $flag = [];
            // 出货成功才算是使用优惠券成功
            if ($this->order['out_status'] == 4) {
                $flag[] = $this->incActivityCoupon(['c_id' => $used['c_id']], 'used_num');
                actionLog($this->getLS(),'增加优惠券活动使用数量',"DataUpload");
                $update['status'] = 2;
                $update['used_time'] = time();
                $update['used_date'] = strtotime(date("Y-m-d"));
                $flag[] = $this->updateActivityCouponUsed($update, ['order_id' => $this->order['order_id']]);
                actionLog($this->getLS(),'修改优惠券使用记录为已使用',"DataUpload");
            } else {
                if ($used['code_type'] == 1) {
                    // 随机码的修改为待使用
                    $flag[] = $this->updateActivityCouponUsed(['status' => 1], ['order_id' => $this->order['order_id'], 'code_type' => 1]);
                    actionLog($this->getLS(), '修改优惠券使用记录为已使用', "DataUpload");
                }
                if ($used['code_type'] == 2) {
                    // 固定码的修改为已作废
                    $flag[] = $this->updateActivityCouponUsed(['status' => 4], ['order_id' => $this->order['order_id'], 'code_type' => 2]);
                    actionLog($this->getLS(), '修改优惠券使用记录为已使用', "DataUpload");
                }
            }
            actionLog($flag,'处理结果',"DataUpload");
        }
    }

    /**
     * 处理提货码
     */
    protected function handlePick()
    {
        $pickCode = $this->getActivityPickCodeFind(['trade_no' => $this->order['trade_no']],'apc_id,ap_id,code,order_id,trade_no,m_id,machine_id,machine_name,pick_type,status,used_time');
        if ($pickCode) {
            $update['apc_id'] = $pickCode['apc_id'];
            $update['order_id'] = $this->order['order_id'];
            $update['used_time'] = time();
            $update['m_id'] = $this->order['m_id'];
            $update['machine_id'] = $this->order['machine_id'];
            $update['machine_name'] = $this->order['machine_name'];
            // 出货成功才是使用成功
            if ($this->order['out_status'] == 4) {
                $adStatus = 1;
                $aaStatus = "PICKED";
                $update['status'] = 2;
            } else {
                $adStatus = 6;
                $aaStatus = "OPEN";
                $update['status'] = 1;
            }
            // 网上预售的订单，出货成功需要发至商城或第三方平台核销
            if ($pickCode['pick_type'] == 3) {
                $advance = $this->getApiAdvanceFind(['apc_id' => $pickCode['apc_id']]);
                if (!$advance) {
                    actionLog($pickCode, '未找到API预订记录，跳过核销回调', 'DataUpload');
                } else {
                $flag[] = $this->updateApiAdvance(['status' => $aaStatus,"pick_time" => date("Y-m-d H:i:s")],['apc_id' => $pickCode['apc_id']]);
                actionLog($this->getLS(),'【SQL】修改API预订商品记录',"DataUpload");
                $details = $this->getSaleOrdersDetailsList(['order_id' => $advance['order_id']],0,'g_id product_id,success_quantity,fail_quantity');
                $details = $details->toArray();
                $detail_status = "ALL PENDING";
                if ($this->order['total_quantity'] != array_sum(array_column($details,'success_quantity'))) $detail_status = "PARTIAL MISVEND";
                if ($this->order['total_quantity'] == array_sum(array_column($details,'fail_quantity'))) $detail_status = "ALL MISVEND";
                $message = [
                    "status" => $adStatus,
                    "machine_id" => $advance['machine_id'],
                    "machine_name" => $advance['machine_name'],
                    "order_no" => $advance['trade_no'],
                    "pick_code" => $advance['pick_code'],
                    "payment_method" => $advance['payment_method'],
                    "quantity" => $advance['quantity'],
                    "detail_status" => $detail_status,
                    "products_list" => json_encode($details,320),
                ];
                $insertCallback = [
                    "aa_id" => $advance['aa_id'],
                    "notify_url" => $advance['notify_url'],
                    "callback_type" => 2,
                    "message" => json_encode($message,320),
                ];
                $ac_id = $this->addApiCallback($insertCallback);
                $ac = $this->getApiCallbackFind(['ac_id' => $ac_id]);
                }
            }
            $flag[] = $this->updateActivityPickCode($update);
            actionLog($this->getLS(),'【SQL】修改取货码使用记录',"DataUpload");
            actionLog($flag,'处理结果集',"DataUpload");
        }
    }

    /**
     * 处理抽奖使用记录
     */
    protected function handleLottery()
    {
        $lottery = $this->getActivityLotteryUsedFind(['order_id' => $this->order['order_id']],"*");
        if ($lottery) {
            $update['alu_id'] = $lottery['alu_id'];
            $update['status'] = 2;
            // 修改为已使用
            $this->updateActivityLotteryUsed($update);
            actionLog($this->getLS(),'【SQL】修改抽奖记录为已使用',"OutGoods");
        }
    }

    /**
     * 处理满减满送活动
     */
    protected function handleFd()
    {
        $fdu = $this->getActivityFdUsedFind(['order_id' => $this->order['order_id']]);
        if ($fdu){
            $update['fdu_id'] = $fdu['fdu_id'];
            $update['used_time'] = time();
            $this->updateActivityFdUsed($update);
            actionLog($this->getLS(),"【SQL】修改满减满送记录使用时间点","OutGoods");
        }
    }

    /**
     * 触发会员支付、丽呈线上支付、机器人线上支付出货结果推送
     */
    protected function handleTripPayCallback()
    {
        if (in_array($this->order['pay_type'],[5,6,7])) {
            $sp = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id'],'sm.s_type' => 1]);
            if (!is_array($sp)) {
                actionLog($sp,'查询收款策略结果返回');
                return $sp;
            }
            if ($sp) {
                // 出货成功才是使用成功
                if ($this->order['out_status'] == 4) {
                    $adStatus = 1;
                    $details_status = "ALL PENDING";
                } else {
                    $adStatus = 6;
                    $details_status = "ALL MISVEND";
                }
                actionLog(['details_status' => $details_status,'adStatus' => $adStatus],'adStatus');
                $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']],0,'g_id product_id,quantity, success_quantity,fail_quantity,out_port');
                $details = $details->toArray();
//                if ($this->order['total_quantity'] != array_sum(array_column($details,'success_quantity'))) $detail_status = "PARTIAL MISVEND";
//                if ($this->order['total_quantity'] == array_sum(array_column($details,'fail_quantity'))) $detail_status = "ALL MISVEND";
                $message = [
                    "status" => $adStatus,
                    "machine_id" => $this->order['machine_id'],
                    "machine_name" => $this->order['machine_name'],
                    "order_no" => $this->order['out_trade_no'],
                    "pick_code" => "",
                    "payment_method" => "",
                    "quantity" => array_sum(array_column($details,'quantity')) ?? 0,
                    "detail_status" => $details_status,
                    "products_list" => json_encode($details,320),
                ];

                actionLog($message,'需要推送的数据');
                $insertCallback = [
                    "aa_id" => 0,
                    "notify_url" => $sp['callbackUrl'],
                    "callback_type" => 8,
                    "message" => json_encode($message,320),
                ];
                actionLog($insertCallback,'插入推送数据');
                $ac_id = $this->addApiCallback($insertCallback);
                actionLog($this->getLS(),'添加出货回调通知记录',"OutGoods");
                $ac = $this->getApiCallbackFind(['ac_id' => $ac_id]);
                actionLog($ac,'查询刚添加的出货回调通知记录',"OutGoods");
            }
        }
    }

    protected function handleOrderOutStatusCallback()
    {
        if ((int)$this->order['out_status'] === 4) {
            return $this->addOrderOutStatusCallback('success');
        }
        if ((int)$this->order['out_status'] === 5) {
            return $this->addOrderOutStatusCallback('fail');
        }
        return false;
    }

}
