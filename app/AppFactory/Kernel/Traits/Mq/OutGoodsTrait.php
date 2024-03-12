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
        if ($this->order['out_status'] >= 4) {
            actionLog($this->order,'订单已处理过了','OutGoods');
            return $this->rFail("订单已处理过了");
        }
        $this->startTrans();
        // 处理修改订单及货道数据
        $flag = $this->handleData();
        if ($this->order['discount_price'] > 0) {
            $this->handleCoupon();
        }
        $result = $this->checkFlag($flag);
        if ($result) $this->commitTrans(); else $this->rollbackTrans();
        return $this->rAction($result);
    }

    /**
     * 修改订单、副表、货道
     * @return array
     */
    protected function handleData()
    {
        $this->order['out_status'] = 4;
        $this->order['out_time'] = time();
        $this->order['remark'] = $this->message['main'];
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
                $mc = $this->getMachineChannelFind($whereMc,'mc_id,stock,stock_warning');
                if ($success > 0) {
                    $updateMc['stock'] = bcsub($mc['stock'],$success);
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

}