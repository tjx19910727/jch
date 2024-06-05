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
            if ($this->order['order_type'] == 2) {
                $this->handleCoupon();
            }
            if ($this->order['order_type'] == 3) {
                $this->handlePick();
            }
            if ($this->order['order_type'] == 4) {
                $this->handleLottery();
            }
            if ($this->order['order_type'] == 5) {
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
        $this->order['remark'] = json_encode($this->message['main'],320);

        $insertGChange = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "ao_id" => $this->machine['ao_id'],
        ];
        foreach ($this->message['main'] as $key => $value) {
            $position = $key;
            foreach ($value as $vv) {
                $channel_code = $vv[0];
                $success = $vv[2];
                $fail = $vv[3];
                $deliver_pics = $vv[4] ?? "";

                // 修改订单副表
                $where['order_id'] = $this->order['order_id'];
                $where['channel_position'] = $position;
                $where['channel_code'] = $channel_code;
                $update['success_quantity'] = $success;
                $update['fail_quantity'] = $fail;
                $update['deliver_pics'] = $deliver_pics;
                $flag[] = $this->updateSaleOrdersDetails($update,$where);
                actionLog($this->getLS(),'【SQL】修改订单副表','OutGoods');

                // 修改货道
                $updateMc = [];
                $whereMc['channel_code'] = $channel_code;
                $whereMc['m_id'] = $this->machine['m_id'];
                $whereMc['channel_position'] = $position;
                $mc = $this->getMachineChannelFind($whereMc,'mc_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,stock_warning');
                if ($success > 0) {
                    if ($this->order['order_type'] == 3 && $this->getActivityPickCodeValue(['order_id' => $this->order['order_id']],'pick_type') == 3) {
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
                $update['status'] = 2;
                // 网上预售的订单，出货成功需要发至商城或第三方平台核销
                if ($pickCode['pick_type'] == 3) {

                }
            } else {
                // 网上预售的订单修改为待使用，即取货失败
//                if ($pickCode['pick_type'] == 3) {
                $update['status'] = 1;
//                }
            }
            $this->updateActivityPickCode($update);
            actionLog($this->getLS(),'【SQL】修改取货码使用记录',"DataUpload");
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

}