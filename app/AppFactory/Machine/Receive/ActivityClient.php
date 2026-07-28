<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 16:11
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Model\Revenue\RevenueOrderModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\AppFactory\Kernel\Service\Revenue\RevenueCouponService;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryConfigTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;

class ActivityClient extends ReceiveBaseClient
{
    use ActivityCouponTrait, ActivityCouponUsedTrait;
    use ActivityLotteryTrait, ActivityLotteryConfigTrait, ActivityLotteryContentTrait, ActivityLotteryUsedTrait, ActivityLotteryUsedGoodsTrait;
    use ActivityPickTrait, ActivityPickCodeTrait;
    use ActivityFdTrait, ActivityFdContentTrait, ActivityFdUsedTrait;
    use ActivityMachineTrait, ActivityGoodsTrait;
    use SaleOrdersTrait;
    use MachineChannelTrait;
    use BeforeOrderPaymentTrait, AfterOrderPaymentTrait;
    use StrategyMachineTrait, StrategyManagerTrait;
    use ApiAdvanceTrait;
    use AuthManagerMachineTrait;

    protected $order;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
    }

    /**
     * 销毁实例时触发
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $msgId = isset($this->data['msg_id']) ? $this->data['msg_id'] : '';
        if ($msgId === '') return;
        $result = $this->updateMachineMqRecord(['status' => 2, 'msg_id' => $msgId], ['msg_id' => $msgId]);
        actionLog($result, '处理完成时修改状态为已处理');
    }

    /**
     * 获取适用设备的优惠券活动列表
     * @return array|string
     */
    public function getByMachine()
    {
        return $this->rQ($this->getAcByMachine());
    }

    /**
     * 使用优惠券码获取优惠券信息
     * @return array|string
     */
    public function getByCode()
    {
        $ac = $this->getAcByCode();
        if (is_string($ac)) {
            return $this->rFail($ac);
        }
        return $this->r(200, $this->lang("query_success"), ['ac' => $ac]);
    }

    /**
     * 通过设备获取取货码活动信息
     * @return array|string
     */
    public function getApByMachine()
    {
        return $this->rQ($this->getActivityPickByMachine());
    }

    /**
     * 通过取货码查询活动
     * @return array|string
     */
    public function getApByCode()
    {
        $apc = $this->getActivityPickByCode();
        if (is_string($apc)) {
            return $this->rFail($apc);
        }
        return $this->r(200, $this->lang("query_success"), ['apc' => $apc]);
    }

    /**
     * 使用优惠券
     * @return array|bool|\think\response\Json
     */
    public function useCoupon()
    {
        $orderId = intval($this->data['order_id']);
        $couponCode = trim(strval($this->data['coupon_code']));
        $this->startTrans();
        try {
            $this->order = SaleOrdersModel::where([
                'order_id' => $orderId,
                'm_id' => $this->machine['m_id'],
            ])->lock(true)->find();
            if (!$this->order) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VActivityPickCode.order_no_data"));
            }
            $this->order = $this->order->toArray();
            $used = $this->getActivityCouponUsedFind(
                ['order_id' => $orderId],
                'cu_id,code'
            );
            if ($used && is_object($used) && method_exists($used, 'toArray')) {
                $used = $used->toArray();
            }
            $sameCoupon = $used && trim(strval($used['code'] ?? '')) === $couponCode;
            $payStatus = intval($this->order['pay_status'] ?? 0);
            if ($payStatus == 3) {
                if (!$sameCoupon) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VOrderPay.pay_status3"));
                }
            } elseif ($payStatus != 1 || intval($this->order['out_status'] ?? 0) != 1) {
                $this->rollbackTrans();
                return $this->rFail("订单当前状态不允许使用优惠券");
            }
            if (!$sameCoupon) {
                $result = $this->orderUseCoupon();
                if ($result !== true) {
                    $this->rollbackTrans();
                    return $result;
                }
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $zeroPay = $this->completeZeroPayOrderIfNeeded($orderId, 'use_coupon_zero_pay');
        if (!($zeroPay['success'] ?? false)) {
            return $this->r(300, $zeroPay['msg'] ?? $this->lang("action_fail"));
        }
        $data = $zeroPay['order'] ?? $this->buildOrderPayActionData($this->order);
        return $this->r(200, "操作成功", $data);
    }

    /**
     * 使用分账优惠券
     * @return array|bool|\think\response\Json
     */
    public function useRevenueCoupon()
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$this->order) return $this->r(100, $this->lang("VActivityPickCode.order_no_data"));
        if ($this->order['out_status'] != 1) return $this->r(100, $this->lang("VActivityPickCode."));

        if (isset($this->data['coupon_code'])) {
            try {
                $result = $this->orderUseRevenueCoupon(trim(strval($this->data['coupon_code'])));
                if ($result !== true) {
                    return $result;
                }
                $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, '*');
            } catch (\Exception $e) {
                actionException($e, 1);
                return $this->rTryCatch($e->getMessage());
            }
        }
        return $this->r(200, "操作成功", $this->order);
    }

    protected function orderUseRevenueCoupon($couponCode)
    {
        if (!preg_match('/^[1-9][0-9]{5}$/', $couponCode)) {
            return $this->rFail($this->lang("VActivityCoupon.check_no_code"));
        }

        $coupon = RevenueCouponService::findEnabledCouponByCode($couponCode);
        if (!$coupon) {
            return $this->rFail($this->lang("VActivityCoupon.check_no_code"));
        }
        if (!is_array($coupon)) $coupon = $coupon->toArray();

        $usable = RevenueCouponService::checkUsable($coupon);
        if (!$usable['usable']) {
            return $this->rFail($usable['message']);
        }

        $matched = RevenueCouponService::matchScope($coupon, $this->getRevenueCouponOrderArray(), $this->getOrderDetailsForRevenueCouponScope());
        if (!$matched['matched']) {
            return $this->rFail("优惠券不适用于当前设备或订单商品");
        }

        $usedCode = trim(strval($this->order['revenue_coupon_code'] ?? ''));
        if ($usedCode !== '' && $usedCode !== $couponCode) {
            return $this->rFail("订单已使用其他分账优惠券");
        }
        if ($usedCode === $couponCode) {
            actionLog(['order_id' => $this->order['order_id'], 'coupon_code' => $couponCode], '分账优惠券已绑定订单');
            return true;
        }

        $this->startTrans();
        try {
            $applyResult = $this->applyRevenueCouponDiscount($coupon, $matched, $couponCode);
            if ($applyResult !== true) {
                $this->rollbackTrans();
                return $applyResult;
            }
            actionLog($this->getLS(), '绑定分账优惠券到订单');
            actionLog([
                'order_id' => $this->order['order_id'],
                'coupon_code' => $couponCode,
                'rr_id' => intval($coupon['rr_id']),
            ], '分账优惠券使用信息');

            $this->order['revenue_coupon_code'] = $couponCode;
            $refreshResult = $this->refreshPendingRevenueAfterRevenueCoupon();
            if ($refreshResult !== true) {
                $this->rollbackTrans();
                return $refreshResult;
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
        return true;
    }

    protected function applyRevenueCouponDiscount(array $coupon, array $matched, $couponCode)
    {
        $discountAmount = RevenueCouponService::calculateOrderDiscountAmount($coupon, $matched['base_amount']);
        $orderTotal = bcadd(strval($this->order['total_price'] ?? 0), '0', 2);
        if (bccomp($discountAmount, $orderTotal, 2) > 0) {
            $discountAmount = $orderTotal;
        }

        $updateOrder = [
            'order_id' => $this->order['order_id'],
            'revenue_coupon_code' => $couponCode,
            'revenue_coupon_discount_type' => intval($coupon['discount_type'] ?? 0),
            'revenue_coupon_discount_value' => bcadd(strval($coupon['discount_value'] ?? 0), '0', 3),
            'revenue_coupon_discount_amount' => $discountAmount,
        ];
        if (bccomp($discountAmount, '0.01', 2) >= 0) {
            if (empty($this->order['retail_price'])) {
                $updateOrder['retail_price'] = $orderTotal;
            }
            $updateOrder['discount_price'] = bcadd(strval($this->order['discount_price'] ?? 0), $discountAmount, 2);
            $updateOrder['total_price'] = $this->subtractRevenueCouponDiscount($orderTotal, $discountAmount);
            $detailResult = $this->applyRevenueCouponDiscountToDetails($matched['details'] ?? [], $discountAmount);
            if ($detailResult !== true) {
                return $detailResult;
            }
            $this->order['discount_price'] = $updateOrder['discount_price'];
            $this->order['total_price'] = $updateOrder['total_price'];
            if (isset($updateOrder['retail_price'])) {
                $this->order['retail_price'] = $updateOrder['retail_price'];
            }
        }

        $this->updateSaleOrders($updateOrder, [], array_keys($updateOrder));
        $this->order['revenue_coupon_discount_type'] = $updateOrder['revenue_coupon_discount_type'];
        $this->order['revenue_coupon_discount_value'] = $updateOrder['revenue_coupon_discount_value'];
        $this->order['revenue_coupon_discount_amount'] = $updateOrder['revenue_coupon_discount_amount'];
        return true;
    }

    protected function applyRevenueCouponDiscountToDetails(array $details, $discountAmount)
    {
        if (!$details) {
            return $this->rFail("优惠券适用订单明细为空");
        }
        $baseAmount = '0.00';
        foreach ($details as $detail) {
            $baseAmount = bcadd($baseAmount, bcadd(strval($detail['_scope_amount'] ?? ($detail['total_sod_price'] ?? 0)), '0', 2), 2);
        }
        if (bccomp($baseAmount, '0.01', 2) < 0) {
            return $this->rFail("优惠券适用金额不足");
        }

        $remainDiscount = bcadd(strval($discountAmount), '0', 2);
        $count = count($details);
        foreach ($details as $index => $detail) {
            $sodId = intval($detail['sod_id'] ?? 0);
            if ($sodId <= 0) {
                continue;
            }
            $detailAmount = bcadd(strval($detail['total_sod_price'] ?? 0), '0', 2);
            $scopeAmount = bcadd(strval($detail['_scope_amount'] ?? $detailAmount), '0', 2);
            if ($index === $count - 1) {
                $sodDiscount = $remainDiscount;
            } else {
                $sodDiscount = bcadd(bcmul($discountAmount, bcdiv($scopeAmount, $baseAmount, 6), 6), '0', 2);
            }
            if (bccomp($sodDiscount, $detailAmount, 2) > 0) {
                $sodDiscount = $detailAmount;
            }
            if (bccomp($sodDiscount, '0.01', 2) < 0) {
                continue;
            }
            $remainDiscount = bcsub($remainDiscount, $sodDiscount, 2);
            $updateSod = [
                'sod_id' => $sodId,
                'discount_price' => bcadd(strval($detail['discount_price'] ?? 0), $sodDiscount, 2),
                'total_sod_price' => $this->subtractRevenueCouponDiscount($detailAmount, $sodDiscount),
            ];
            $this->updateSaleOrdersDetails($updateSod);
        }
        return true;
    }

    protected function subtractRevenueCouponDiscount($amount, $discount)
    {
        $amount = bcadd(strval($amount), '0', 2);
        $discount = bcadd(strval($discount), '0', 2);
        if (bccomp($amount, '0', 2) < 0) $amount = '0.00';
        if (bccomp($discount, '0', 2) < 0) $discount = '0.00';
        if (bccomp($discount, $amount, 2) > 0) $discount = $amount;
        $result = bcsub($amount, $discount, 2);
        return bccomp($result, '0', 2) < 0 ? '0.00' : $result;
    }

    protected function getRevenueCouponOrderArray()
    {
        if (is_object($this->order) && method_exists($this->order, 'toArray')) {
            return $this->order->toArray();
        }
        return is_array($this->order) ? $this->order : [];
    }

    protected function refreshPendingRevenueAfterRevenueCoupon()
    {
        $order = $this->getRevenueCouponOrderArray();
        if (empty($order['order_id']) || floatval($order['total_price'] ?? 0) <= 0 || intval($order['pay_channel'] ?? 0) <= 0) {
            return true;
        }
        $pendingCount = RevenueOrderModel::where(['order_id' => intval($order['order_id']), 'status' => 0])->count();
        if (!$pendingCount) {
            return true;
        }
        $this->order['details'] = $this->getOrderDetailsForRevenueCouponScope();
        $result = $this->countIncome();
        if (!$result) {
            return $this->rFail($this->revenueError ?: "重新生成分账订单失败");
        }
        return true;
    }

    protected function getOrderDetailsForRevenueCouponScope()
    {
        if (isset($this->order['details']) && $this->order['details']) {
            $details = $this->order['details'];
            if (!is_array($details)) $details = $details->toArray();
            return $details;
        }
        $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, 'sod_id,g_id,mg_id,total_sod_price,quantity');
        if (!$details) return [];
        return is_array($details) ? $details : $details->toArray();
    }

    /**
     * 使用取货码
     * @return array|int|string|\think\response\Json
     */
    public function usePickCode()
    {

        try {
            $this->startTrans();
            $apc = $this->getActivityPickByCode();
            actionLog($apc, "使用的取货码数据");
            if (!is_array($apc)) {
                $this->rollbackTrans();
                return $this->r(100,$apc);
            }
            $flag[] = 1;
            // 预订订单取货
            if ($apc['pick_type'] == 3) {
                $this->order = $this->getSaleOrdersFind(['order_id' => $apc['order_id']]);
                if (!$this->order) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VActivityPickCode.order_no_data"));
                }
                $this->order = $this->order->toArray();
                if ($this->order['m_id'] != $this->machine['m_id']) {
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang("VActivityPickCode.pick_code_can_not_use"));
                }
                if ($this->order['out_status'] != 1) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VActivityPickCode.out_status1"));
                }
            } else {
                // 系统随机取货，随机获取货架商品信息生成取货商品，整理carList，如果pick_type==2，则carList由外部传入
                if ($apc['pick_type'] == 1) {
                    $ap = $apc['ap'];
                    if (!$ap['ag']) {
                        $this->rollbackTrans();
                        return $this->r(100,$this->lang("VActivityPick.ag_not_data"));
                    }
                    $apg_id = array_column($ap['ag'], 'g_id');
                    $whereMc[] = ['g_id', 'in', $apg_id];
                    $whereMc['status'] = 1;
                    $whereMc['m_id'] = $this->machine['m_id'];
                    $whereMc[] = ['stock', '>',0];
                    $mc = $this->getMachineChannelColumn($whereMc, 'mc_id');
                    if (!$mc) {
                        $this->rollbackTrans();
                        return $this->r(100,$this->lang("VActivityPickCode.mc_id_empty"));
                    }
                    $mc_count = count($mc);
                    $num = random_int(1, $mc_count);
                    // 只取一个商品
                    $this->data['carList'][] = ["mc_id" => $mc[($num - 1)], 'quantity' => 1];
                }
                $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
                $order = [
                    "trade_no" => $trade_no,
                    "mch_no" => $trade_no,
                    "m_id" => $this->machine['m_id'],
                    "machine_name" => $this->machine['machine_name'],
                    "machine_id" => $this->machine['machine_id'],
                    "order_type" => 3,
                    "pay_status" => 3,
                    "pay_time" => time(),
                    "pay_code" => $this->data['pick_code'],
                    "apc_id" => $apc['apc_id'],
                    "ao_id" => $this->machine['ao_id'],
                    "create_date" => strtotime(date("Y-m-d")),
                ];
                $order_id = $this->addSaleOrders($order);
                if ($order_id) {
                    $mcField = "mc_id,channel_code,frozen_stock,stock,shelf_way,channel_position,manufacture_time,sell_by_date,retail_price,
                            mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number,
                            cost_price,market_price";
                    $updateOrder['order_id'] = $order_id;
                    $updateOrder['total_quantity'] = 0;
                    foreach ($this->data['carList'] as $key => $value) {
                        $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']], $mcField);
                        if (!$mc) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.channel_no_data"));
                        }
                        $mc = $mc->toArray();
                        if ($mc['stock'] < $value['quantity']) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.under_stock"));
                        }
                        while($value['quantity'] > 0) {
                            $details = [
                                "order_id" => $order_id,
                                // 20250620 林琼虹与财务确认，一切的活动价，优惠价，折扣价，赠品成本等都是在优惠额体现，而原订单金额就是按商品原本的售价*数量得出。实际销售额 = 设备销售额 - 退款金额（如有）-优惠额
//                                "retail_price" => 0,
                                "total_sod_price" => 0,
                                "retail_price" => $mc['retail_price'],
                                "discount_price" => $mc['retail_price'],
                                "quantity" => 1,
                            ];
                            $details = array_merge($details, $mc);
                            $sod_id = $this->addSaleOrdersDetails($details);
                            actionLog($this->getLS(), '生成订单详情数据');
                            if ($sod_id) {
                                // 20250620 林琼虹与财务确认，一切的活动价，优惠价，折扣价，赠品成本等都是在优惠额体现，而原订单金额就是按商品原本的售价*数量得出。实际销售额 = 设备销售额 - 退款金额（如有）-优惠额
                                $updateOrder['discount_price'] =  ($updateOrder['discount_price'] ?? 0) + $details['discount_price'];
                                $updateOrder['retail_price'] = ($updateOrder['retail_price'] ?? 0) + $details['discount_price'];

                                $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], 1);
                                $value['quantity']--;
                            } else {
                                $this->rollbackTrans();
                                return $this->r(300, $this->lang("VSubCar.make_order_details_fail"));
                            }
                        }
                    }
                    $flag[] = $this->updateSaleOrders($updateOrder);
                    actionLog($this->getLS(), '【SQL】修改订单数据');
                    $flag[] = $this->updateActivityPickCode(['apc_id' => $apc['apc_id'], 'order_id' => $order_id, 'trade_no' => $trade_no]);
                    actionLog($this->getLS(), '【SQL】修改取货码数据');
                }
                if (!$order_id) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VActivityPickCode.add_order_fail"));
                }
                $this->order = $this->getSaleOrdersFind(['order_id' => $order_id])->toArray();
            }
            $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
            if ($this->order['total_price'] > 0) {
                $flag[] = $this->countIncome();
            }
            actionLog($flag,'处理事务结果');
            $result = flag_check($flag);
            if ($result) {
                $result = $this->outGoods();
                actionLog(@obj2arr($result),'出货结果');
                if (is_object($result)) {
                    $result = obj2arr($result);
                    if (isset($result['state']) && $result['state'] != 200) {
                        $this->rollbackTrans();
                        return $result;
                    }
                }
                if ($result === false) {
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang("VOutGoods.send_out_goods_fail"));
                }
                $details = $this->order['details'] ?? null;
                if (isset($this->order['details'])) {
                    unset($this->order['details']);
                }
                $this->order["factory"] = empty($this->machine['factory'])?$this->machine['factory']:'';
                $this->order["inventory_location"] = empty($this->machine['inventory_location'])?$this->machine['inventory_location']:'';
                $this->updateSaleOrders($this->order);
                actionLog($this->getLS(),'使用取货码完成修改订单');
                $this->updateActivityPickCode(['apc_id' => $apc['apc_id'], 'status' => 5]);
                actionLog($this->getLS(),'使用取货码成功修改取货码状态');
                if ($apc['pick_type'] == 3) {
                    $this->updateApiAdvance(['status' => "PROCESSING"], ['apc_id' => $apc['apc_id']]);
                    actionLog($this->getLS(), '修改预订商品记录表');
                }
                $this->commitTrans();
                $this->order['details'] = $details;
                return $this->r(200,$this->lang("action_success"),$this->order);
            }
            $this->rollbackTrans();
            return $this->rFail();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取设备绑定的付费抽奖活动
     * @return array|string
     */
    public function getAlByMachine()
    {
        return $this->rQ($this->getActivityLotteryByMachine());
    }

    /**
     * 生成抽奖订单信息
     * @return array|string
     */
    public function lotteryOrder()
    {
        if ($this->data['pay_type'] != 4 && $this->data['pay_type'] != 0) return $this->rFail($this->lang("VSubCar.pay_type_no_range"));
        $updateAl = [];
        $alc = $this->getActivityLotteryConfigFind(['alc_id' => $this->data['alc_id']]);
        if (!$alc) return $this->r(300,$this->lang("alc_no_data"));
        $alc = $alc->toArray();
        // 检查活动内容
        $al = $this->getActivityLotteryFind(['al_id' => $alc['al_id']]);
        if (!$al) return $this->r(300,$this->lang("VActivityLottery.al_no_data"));
        if ($al['status'] == 3) return $this->r(300,$this->lang("VActivityLottery.status3"));
        if ($al['status'] == 4) return $this->r(300,$this->lang("VActivityLottery.status4"));
        if ($al['start_time'] > time()) return $this->r(300,$this->lang("VActivityLottery.al_not_begin"));
        if ($al['end_time'] > 0 && $al['end_time'] < time()) {
            $updateAl['status'] = 3;
            return $this->r(300,$this->lang("VActivityLottery.status3"));
        }
        if ($al['status'] == 1) {
            $updateAl['status'] = 2;
        }
        if ($updateAl) {
            $updateAl['al_id'] = $al['al_id'];
            $this->updateActivityLottery($updateAl);
        }
        // 增加赠送次数
        $alc['active_num'] += $alc['gifts_num'];

        // 检查活动商品
        $content = $this->getActivityLotteryContentList(['al_id' => $al['al_id']], 0, 'c_id,retain_num,g_id,probability,g_name');
        actionLog($content,'活动商品内容');
        if (!$content) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $content = $content->toArray();
        // 总中奖概率，必须要刚好100%
        $totalProbability = array_sum(array_column($content, "probability"));
        if ($totalProbability != 100) return $this->r(300,$this->lang("probability_no_100"));

        // 如果存在赠送商品，则校对数量再加1
        $checkStock = $alc['active_num'];
        if ($alc['designated_gift']) $checkStock = $alc['active_num'] + 1;
        $mcList = [];
        // 循环活动内容，
        foreach ($content as $k => $v) {
            // 判断货架正常，有这个商品
            $mc = $this->getMachineChannelFind(['m_id' => $this->machine['m_id'], 'status' => 1, 'g_id' => $v['g_id'],['stock',">=",$checkStock + $v['retain_num'] ]], 'channel_code,stock','stock desc');
            if (!$mc) {
                actionLog($this->getLS(),'货道禁用或没库存');
                continue;
//                return $this->rFail($this->lang("VActivityLottery.mc_no_data") . "【" . $v['g_name'] . "】" . $this->lang("VActivityLottery.goods_not_data"));
            }
            $mcList[] = $mc;
        }
        // 所有商品都没库存或禁用，则返回无活动商品
        if (!$mcList) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "order_type" => 4,
            "lottery_id" => $al['al_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "total_price" => $this->data['total_price'],
            "total_quantity" => $alc['active_num'],  // 商品总数，执行完成抽奖后需要重新校对
            "create_date" => strtotime(date("Y-m-d")),
        ];
        if ($this->data['total_price'] == 0) {
            $order['pay_status'] = 3;
            $order['pay_time'] = time();
            $order['pay_type'] = 0;
            $order['pay_method'] = 1;
        }
        // 添加订单信息
        $order['order_id'] = $this->addSaleOrders($order);
        if ($order['order_id']) {
            // 添加抽奖记录主体信息
            $alu = [
                "al_id" => $al['al_id'],
                "alc_id" => $alc['alc_id'],
                "order_id" => $order['order_id'],
                "trade_no" => $order['trade_no'],
                "m_id" => $this->machine['m_id'],
                "machine_id" => $this->machine['machine_id'],
                "machine_name" => $this->machine['machine_name'],
                "price" => $al['price'],
                "quantity" => $alc['active_num'],  // 抽奖总次数，包含赠送次数，不包含赠送商品
                "total_price" => $this->data['total_price'],
                "active_type" => $alc['active_type'],
                "used_date" => strtotime(date("Y-m-d")),
            ];
            $result = $this->addActivityLotteryUsed($alu);
            if ($result) {
                return $this->r(200, $this->lang("VSubCar.make_order_success"), $order);
            }
        }
        return $this->r(100, $this->lang("VSubCar.make_order_fail"));
    }

    /**
     * 执行抽奖
     * @return array|string
     * @throws \Exception
     */
    public function luckyDrawResult()
    {
        $order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$order) {
            return $this->r(300, $this->lang("VActivityLottery.order_no_data"));
        }
        if ($order['order_type'] != 4) return $this->r(300, $this->lang("VActivityLottery.order_type_error"));
        if ($order['pay_status'] != 3) return $this->r(300, $this->lang("VActivityLottery.order_no_pay"));

        $used = $this->getActivityLotteryUsedFind(['order_id' => $order['order_id']], 'alu_id,al_id,alc_id,quantity,used_quantity');
        if (!$used) {
            return $this->r(300, $this->lang("VActivityLottery.used_no_data"));
        }
        $used = $used->toArray();
        actionLog($used,'执行抽奖查询已使用抽奖数据');
        // 已使用抽奖次数大于等于抽奖总次数，返回抽奖数量已用完
        if ($used['used_quantity'] >= $used['quantity']) {
            return $this->r(300, $this->lang("VActivityLottery.lucky_draw_ended"));
        }

        $alc = $this->getActivityLotteryConfigFind(['alc_id' => $used['alc_id']]);
        if (!$alc) return $this->r(300, $this->lang("VActivityLottery.alc_no_data"));
        $content = $this->getActivityLotteryContentList(['al_id' => $used['al_id']], 0, '*', "probability asc");
        if (!$content) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $content = $content->toArray();
        actionLog($content,'抽奖活动内容');

        // 总中奖概率
        $totalProbability = array_sum(array_column($content, 'probability'));
        if ($totalProbability != 100) return $this->r(300,$this->lang("VActivityLottery.probability_no_100"));
        // 本次执行抽奖次数初始化，总数量减去已抽次数
        $quantity = bcsub($used['quantity'], $used['used_quantity']);
        if ($quantity <= 0) return $this->r(300,$this->lang("VActivityLottery.used_quantity_is_null"));
        // 单抽状态下，每次执行抽奖时抽奖次数重置为1
        if ($alc['active_type'] == 1) $quantity = 1;

        // 库存不足或禁用货架，不存在这个商品上架的情况下不参与抽奖
        $whereMcGid = function ($query) use ($quantity)  {
            $query->where("`m_id` = " . $this->machine['m_id'] . " AND (`status` <> 1 OR `stock` >= $quantity)");
        };
        $channel = $this->getMachineChannelColumn($whereMcGid,'g_id');
        actionLog($this->getLS(),'【SQL】获取设备货道商品ID');
        actionLog($channel,'货道商品ID');
        $temp = [];
        // 有这个商品在设备货道上，正常且有库存的，重置抽奖列表
        foreach ($content as $cK => $cV) {
            if (in_array($cV['g_id'],$channel)) {
                $temp[] = $cV;
            }
        }
        actionLog($temp,'重新整理的抽奖活动内容');
        if (!$temp) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $content = $temp;
        $totalProbability = array_sum(array_column($content, 'probability'));
        actionLog($totalProbability,'重新计算的总中奖率');

        // 执行抽奖主程序
        $giftNum = 0;
        $list = [];
        if ($content) {
            // 多抽循环，每次抽奖结果放至中奖列表
            for ($i = 0; $i < $quantity; $i++) {
                // 已抽奖次数+1
                $used['used_quantity']++;

                // 每次抽奖的随机数值
                $random = mt_rand(1, $totalProbability);
                actionLog($random, "第" . $i . "次随机数值");
                $probabilitySum = 0;
                // 抽奖，循环活动内容，以中奖概率从小到大顺序排序，
                foreach ($content as $key => $value) {
                    // 赠送指定商品
                    if ($alc['designated_gif']) {
                        // 是赠送的商品，放入中奖列表队尾
                        if ($alc['designated_gift'] == $value['c_id']) {
                            $value['is_gift'] = 1;
                            $giftNum++;
                            $list[$i + $quantity] = $value;
                        }
                    }
                    // 当前一条抽奖活动内容中奖数值，叠加前面的活动内容中奖数值
                    $probabilitySum = bcadd($probabilitySum, $value['probability']);
                    // 中奖数值大于随机数值即为中奖，活动内容放入中奖数组中，退出当前抽奖循环，执行下轮抽奖
                    actionLog($probabilitySum, "第" . $i . "次" . $value['c_id'] . "活动内容中奖数值");
                    if ($probabilitySum >= $random) {
                        actionLog($value, "第" . $i . "次" . "中奖数据");
                        $list[$i] = $value;
                        break;
                    }
                }
            }
        }
        actionLog($list,'中奖列表');
        $this->startTrans();

        try {// 修改已抽奖次数
            $updateUsed['used_quantity'] = $used['used_quantity'];
            if ($used['used_quantity'] == $used['quantity']) {
                $updateUsed['status'] = 2;
                $updateUsed['used_date'] = strtotime(date("Y-m-d"));
            }
            $this->updateActivityLotteryUsed($updateUsed, ['alu_id' => $used['alu_id']]);
            actionLog($this->getLS(),'执行修改抽奖记录');
            // 没抽中，返回谢谢惠顾
            if (!$list) {
                $this->commitTrans();
                $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0)->toArray();
                return $this->r(200, $this->lang("VActivityLottery.lottery_empty"), ['lottery_list' => $list, "order" => $order]);
            }
            $averagePrice = bcdiv($order['total_price'], $used['quantity'], 3);
            $flag = [];
            $ugAll = [];
            $mcField = "mc_id,shelf_way,channel_position,channel_code,mg_id,g_id,g_name,pic,sku,gc_id,gc_name,cost_price,market_price,stock";
            $updateOrder['discount_price'] = $order['discount_price'];
            $updateOrder['retail_price'] = $order['retail_price'];
            foreach ($list as $lk => $lv) {
                $mc = $this->getMachineChannelFind(['g_id' => $lv['g_id'], 'm_id' => $order['m_id']], $mcField, 'stock desc');
                if (!$mc) {
                    actionLog($this->getLS(),'中奖商品不在这台设备中');
                    $this->rollbackTrans();
                    return $this->r(300,$this->lang("VActivityLottery.mc_no_data"));
                }
                $mc = $mc->toArray();
                $ug = [
                    "al_id" => $used['al_id'],
                    "alu_id" => $used['alu_id'],
                    "alc_id" => $lv['c_id'],
                    "g_id" => $mc['g_id'],
                    "g_name" => $mc['g_name'],
                    "sku" => $mc['sku'],
                    "probability" => $lv['probability'],
                    "mc_id" => $mc['mc_id'],
                    "channel_code" => $mc['channel_code'],
                    "quantity" => 1,
                ];
                // 不存在商品记录则生成，存在商品则数量+1
//                $sod = $this->getSaleOrdersDetailsFind(['order_id' => $order['order_id'], 'mc_id' => $mc['mc_id']]);
//                if (!$sod || ($sod && isset($lv['is_gift']))) {
                    unset($mc['stock']);
                    $insertSod = $mc;
                    $insertSod['order_id'] = $order['order_id'];
                    $insertSod['quantity'] = 1;
                    $insertSod['total_sod_price'] = $averagePrice;
                    $insertSod['retail_price'] = $averagePrice;
                    if (isset($lv['is_gift'])) {
                        $insertSod['total_sod_price'] = 0;
//                        $insertSod['retail_price'] = 0;
                        // 20250620 林琼虹与财务确认，一切的活动价，优惠价，折扣价，赠品成本等都是在优惠额体现，而原订单金额就是按商品原本的售价*数量得出。实际销售额 = 设备销售额 - 退款金额（如有）-优惠额
                        $insertSod['discount_price'] = $insertSod['total_sod_price'];
                        $updateOrder['discount_price'] += $insertSod['discount_price'];
                        $updateOrder['retail_price'] += $insertSod['discount_price'];
                    }
                    $sod_id = $this->addSaleOrdersDetails($insertSod);
                    $flag[] = $sod_id;
//                } else {
//                    $sod = $sod->toArray();
//                    $sod_id = $sod['sod_id'];
//                    $update['sod_id'] = $sod_id;
//                    $update['quantity'] = $sod['quantity'] + 1;
//                    $update['retail_price'] = bcdiv($averagePrice, $update['quantity'], 3);
//                    $flag[] = $this->updateSaleOrdersDetails($update);
//                }
                actionLog($this->getLS(),'订单详情处理SQL');
                $ug['sod_id'] = $sod_id;
                $ugAll[] = $ug;
            }
            // 20250620 林琼虹与财务确认，一切的活动价，优惠价，折扣价，赠品成本等都是在优惠额体现，而原订单金额就是按商品原本的售价*数量得出。实际销售额 = 设备销售额 - 退款金额（如有）-优惠额
            $updateOrder['order_id'] = $order['order_id'];
            $this->updateSaleOrders($updateOrder);
            actionLog($this->getLS(),'【SQL】抽奖赠品增加订单总优惠金额，原订单总金额');
            $flag[] = $this->addActivityLotteryUsedGoodsMore($ugAll);
            actionLog($this->getLS(),'生成中奖记录数据');
            actionLog($flag,'事务执行结果');
            $check = $this->checkFlag($flag);
            if ($check) {
                $this->commitTrans();
                $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0)->toArray();
                return $this->r(200, $this->lang("action_success"), ['lottery_list' => $list, "order" => $order]);
            }
            $this->rollbackTrans();
            return $this->r(300, $this->lang("action_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 抽奖订单出货
     * @return array|string
     * @throws \Exception
     */
    public function lotteryOutGoods()
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$this->order) return $this->r(300,$this->lang("VActivityLottery.order_no_data"));
        if ($this->order['pay_status'] != 3) return $this->r(300,$this->lang("VActivityLottery.order_no_pay"));
        if ($this->order['out_status'] > 1) return $this->r(300,$this->lang("VActivityLottery.is_out_goods"));
        $whereDetails['order_id'] = $this->order['order_id'];
        $used = $this->getActivityLotteryUsedFind(['order_id' => $this->order['order_id']]);
        if (!$used) return $this->r(300, $this->lang("VActivityLottery.used_no_data"));
        $quantity = $this->getSaleOrdersDetailsSum($whereDetails, 'quantity');
        if ($used['quantity'] != $quantity) return $this->r(300, $this->lang("VActivityLottery.quantity_not_match"));
        $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, '*');
        if (!$this->order['details']) return $this->r(300, $this->lang("VActivityLottery.order_details_no_data"));
        $this->startTrans();
        try {
            $flag = [];// 生成分润记录
            $flag[] = $this->countIncome();// 修改分润记录
            $flag[] = $this->settlementRevenue();// 修改为发送出货命令状态
            $updateOrder['order_id'] = $this->order['order_id'];
            $updateOrder['out_status'] = 2;
            if ($this->order['pay_status'] != 3) {
                $updateOrder['pay_status'] = 3;
                $updateOrder['pay_type'] = 0;
                $updateOrder['pay_method'] = 1;
                $updateOrder['pay_time'] = time();
            }
            $flag[] = $this->updateSaleOrders($updateOrder);
            $check = $this->checkFlag($flag);
            if ($check) {
                $this->outGoods();
                $this->commitTrans();
                return $this->r(200, $this->lang("action_success"));
            }
            $this->rollbackTrans();
            return $this->r(300, $this->lang("action_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 根据设备获取满减满送活动信息列表
     * @return array|string
     */
    public function getFdByMachine()
    {
        return $this->rQ($this->getActivityFdByMachine());
    }

    /**
     * 获取当前时间点生效的满减活动及其线上商品配置。
     * @return array|\think\response\Json
     */
    public function getCurrentFdListByMachine()
    {
        return $this->rQ($this->getCurrentActivityFdListByMachine());
    }

    /**
     * 订单使用满减满送活动
     * @return mixed
     */
    public function useFd()
    {
        $result = $this->orderUseFd();
        $resultArr = obj2arr($result);
        if (!is_array($resultArr) || intval($resultArr['state'] ?? 0) != 200) {
            return $result;
        }
        $orderId = intval($this->data['order_id'] ?? (($resultArr['data']['order']['order_id'] ?? 0)));
        if ($orderId <= 0) {
            return $result;
        }
        $zeroPay = $this->completeZeroPayOrderIfNeeded($orderId, 'usefd_zero_pay');
        if (!($zeroPay['success'] ?? false)) {
            return $this->r(300, $zeroPay['msg'] ?? $this->lang("action_fail"));
        }
        if (!($zeroPay['handled'] ?? false)) {
            $resultArr['data']['pay_required'] = true;
            $resultArr['data']['zero_pay'] = false;
            $resultArr['data']['next_action'] = 'pay';
            return $this->r(200, $resultArr['msg'] ?? $this->lang("action_success"), $resultArr['data']);
        }
        $resultArr['data'] = array_merge($resultArr['data'] ?? [], $zeroPay['order']);
        return $this->r(200, $resultArr['msg'] ?? $this->lang("action_success"), $resultArr['data']);
    }
}
