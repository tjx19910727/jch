<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/2
 * Time: 15:53
 */

namespace app\AppFactory\Kernel\Traits\Mq;


trait OutGoodsTrait
{

    /**
     * 出货处理
     * @return int
     */
    public function outGoods()
    {
        $this->order = $this->getSaleOrdersFind(['trade_no' => $this->message['trade_no']]);
        if (!$this->order) {
            actionLog($this->getLS(),'查无订单数据','OutGoods');
            return $this->rFail("查无订单数据");
        }
        $this->order = $this->order->toArray();
        if ($this->order['out_status'] >= 4) {
            actionLog($this->order,'订单已处理过了','OutGoods');
            return $this->rFail("订单已处理过了");
        }
        $this->startTrans();
        try {// 处理修改订单及货道数据
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
            if ($result) $this->commitTrans(); else $this->rollbackTrans();
            return $this->rAction($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 修改订单、副表、货道
     * @return array
     */
    protected function handleData()
    {
        $this->order['out_status'] = 4;
        $this->order['out_time'] = time();
        $this->order['remark'] = "接收到出货结果";

        $insertGChange = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "ao_id" => $this->machine['ao_id'],
        ];
        foreach ($this->message['main'] as $key => $value) {
            $position = $key;
            foreach ($value as $vv) {
                $channel_code = $vv["channel_code"];
                $success = $vv["success_quantity"];
                $fail = $vv["fail_quantity"];
                $deliver_pics = $vv["deliver_pics"] ?? "";
                $out_sequence = $vv["out_sequence"] ?? 1;

                // 修改订单副表
                $where['order_id'] = $this->order['order_id'];
                $where['channel_position'] = $position;
                $where['channel_code'] = $channel_code;
                $update['success_quantity'] = $success;
                $update['fail_quantity'] = $fail;
                $update['deliver_pics'] = $deliver_pics;
                $update['out_sequence'] = $out_sequence;
                $flag[] = $this->updateSaleOrdersDetails($update,$where);
                actionLog($this->getLS(),'【SQL】修改订单副表','OutGoods');

                // 修改货道
                $updateMc = [];
                $whereMc['channel_code'] = $channel_code;
                $whereMc['m_id'] = $this->machine['m_id'];
                $whereMc['channel_position'] = $position;
                $mc = $this->getMachineChannelFind($whereMc,'mc_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,stock_warning');
                if ($success > 0) {
                    // 外部预订提货码订单，减冻结库存
                    if ($this->order['apc_id'] && $this->getActivityPickCodeValue(['order_id' => $this->order['order_id']],'pick_type') == 3) {
                        $updateMc['frozen_stock'] = bcsub($mc['frozen_stock'],$success);
                    } else {
                        $updateMc['stock'] = bcsub($mc['stock'], $success);
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
                    $insertGc['desc'] = "终端销售-货架减库存";
                    $insertGc['position'] = 1;
                    $insertGc['type'] = 2;
                    $this->addGoodsChange($insertGc);

                }
                if ($fail > 0) {
                    $updateMc['status'] = 3;
                    $this->order['out_status'] = 5;
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

    /**
     * 处理优惠券
     */
    protected function handleCoupon()
    {
        $used = $this->getActivityCouponUsedFind(['order_id' => $this->order['order_id']],'c_id,code,code_type');
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
                if ($ac) {
                    $cb = cache("callback0");
                    $cb[] = $ac->toArray();
                    cache("callback0",$cb,60);
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
     * 处理会员支付
     */
    protected function handleTripPayCallback()
    {
        if ($this->order['pay_type'] == 5) {
            $sp = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id']]);
            if ($sp) {
                $sp = $sp->toArray();
                // 出货成功才是使用成功
                if ($this->order['out_status'] == 4) {
                    $adStatus = 1;
                } else {
                    $adStatus = 6;
                }
                $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']],0,'g_id product_id,success_quantity,fail_quantity');
                $details = $details->toArray();
                $detail_status = "ALL PENDING";
                if ($this->order['total_quantity'] != array_sum(array_column($details,'success_quantity'))) $detail_status = "PARTIAL MISVEND";
                if ($this->order['total_quantity'] == array_sum(array_column($details,'fail_quantity'))) $detail_status = "ALL MISVEND";
                $message = [
                    "status" => $adStatus,
                    "machine_id" => $this->order['machine_id'],
                    "machine_name" => $this->order['machine_name'],
                    "order_no" => $this->order['trade_no'],
                    "pick_code" => "",
                    "payment_method" => "",
                    "quantity" => $this->order['total_quantity'],
                    "detail_status" => $detail_status,
                    "products_list" => json_encode($details,320),
                ];
                $insertCallback = [
                    "aa_id" => 0,
                    "notify_url" => $sp['callbackUrl'],
                    "callback_type" => 8,
                    "message" => json_encode($message,320),
                ];
                $ac_id = $this->addApiCallback($insertCallback);
                $ac = $this->getApiCallbackFind(['ac_id' => $ac_id]);
                if ($ac) {
                    $cb = cache("callback0");
                    $cb[] = $ac->toArray();
                    cache("callback0",$cb,60);
                }
            }
        }
    }

}