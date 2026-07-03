<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:17
 */

namespace app\AppFactory\Kernel\Traits\Payment;



use app\AppFactory\Kernel\Support\Trip\Trip;
use app\AppFactory\Kernel\Traits\Api\ApiOutStatusNotifyTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Service\Revenue\RevenueSettlementService;
use think\facade\Db;

trait AfterOrderPaymentTrait
{
    use MachineChannelTrait,AuthManagerTrait, WcBaseTrait, ApiOutStatusNotifyTrait;

    /**
     * 处理支付成功
     */

    /**
     * @var array 分润状态
     */
    protected $revenueStatus = [
        "1" => 2,         // 电子钱包
        "21" => 1,        // 微信企业付款至零钱
        "22" => 1,        // 微信商户转账至零钱
        "3" => 1,         // 支付宝转账至零钱
        "4" => 2,         // 京东收银分账
    ];

    /**
     * 支付成功
     * @return mixed
     */
    public function paymentSuccessful()
    {
        $flag = [];
        if ($this->order['machine_id']) {
            // 发送给设备终端支付成功状态
            actionLog($this->order, '准备发送支付成功MQ');
            $this->sendToMachine(['machine_id' => $this->order['machine_id']], 'paySuccess', ['trade_no' => $this->order['trade_no']]);
        }
        $this->order['pay_status'] = 3;
        $this->order['pay_time'] = time();

        actionLog($this->order, '更新支付时间成功');
        sleep(1);
        if ($this->order['order_type'] != 4 && $this->order['out_status'] == 1) {
            $this->outGoods();
            //这里要新增一步处理。即判断当前订单是否为虚拟货道组合商品，如果是，则需要将所有子商品进行出货处理
        }
        $this->handleHotel(1);
        actionLog($this->order, '订单数据');
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(), '订单修改数据');
        $result = flag_check($flag);
        // 微程同步属于外部链路，失败不能回滚已支付订单状态。
        $this->syncOrderToWcAfterPayment();
        actionLog($flag, '支付成功处理结果flag');
        actionLog($result, '支付成功处理结果');
        $this->machine['machine_id'] = $this->order['machine_id'];
        $this->machine['machine_name'] = $this->order['machine_name'];
        $this->machine['m_id'] = $this->order['m_id'];
        $this->machine['ao_id'] = $this->order['ao_id'];
        
        try{
            @$this->sendNotice();
        } catch (\Exception $e) {
            actionException($e, 1, 'tryCatch');
        }
        return $result;
    }

    protected function syncOrderToWcAfterPayment()
    {
        try {
            $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
            if (!$details) return true;
            if (is_object($details) && method_exists($details, 'toArray')) {
                $details = $details->toArray();
            }
            $syncFlag = false;
            foreach($details as $detail){
                if($detail['wc_order_no']){
                    $syncFlag = true;
                    break;
                }
            }
            if ($syncFlag) {
                $syncResult = $this->orderSync2Wc($this->order);
                actionLog($syncResult, '微程订单同步结果');
            }
        } catch (\Throwable $e) {
            actionException($e, 1, 'tryCatch');
            actionLog([
                'order_id' => $this->order['order_id'] ?? 0,
                'trade_no' => $this->order['trade_no'] ?? '',
                'error' => $e->getMessage(),
            ], '微程订单同步异常，不影响支付成功事务');
        }
        return true;
    }

    /**
     * 出货
     * 支付成功后，将积分信息写入父子订单表中
     * 
     * @return string
     */
    protected function outGoods()
    {
        $details = $this->order['details'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
        if ($details) {
            $contentArr = [];
            $outArr = [];
            // 旧版本数据，待软件更新后删除
            foreach ($details as $k => $v) {
                if (!$v['mc_id']) $v['g_type'] = 1;
                if ($v['g_type'] == 1) {
                    $dc = [
                        $v['channel_code'],
                        $v['quantity'],
                    ];
                    $contentArr[$v['channel_position']][] = $dc;
                }
            }
            // 新数据格式
            $total_points = 0;
            foreach ($details as $k => $v) {
                if (!$v['mc_id']) {
                    $outArr[$v['channel_position']][] = [
                        "channel_code" => $v['channel_code'],
                        "quantity" => $v['quantity'],
                        "is_gift" => $v['is_gift'] ?? 2,
                        "out_port" => $v['out_port'] ?? 1,
                    ];
                    continue;
                } else {
                    $updateSod['sod_id'] = $v['sod_id'];
                    $updateSod['total_sod_points'] = 0;
                    $updateSod['intergral_rate'] = 0;
                    if ($v['channel_code'] == 'Z10') {
                        $mc = $this->getWcMachineChannelFind(['mc_id' => $v['mc_id']]);
                    } else {
                        $mc = $this->getMachineChannelFind(['mc_id' => $v['mc_id']]);
                    }

                    // normalize to array/object as needed
                    $mc = $mc ? (is_array($mc) ? $mc : obj2arr($mc)) : [];

                    //微程积分记录到details表
                    if(!empty($v['wc_order_no'])){
                        $wc_order_no = json_decode($v['wc_order_no'], true);
                        if (is_array($wc_order_no) && isset($wc_order_no['total_sod_points'])) {
                            $updateSod['total_sod_points'] = $wc_order_no['total_sod_points'];
                        }
                    }else{
                        $rate_points = $this->getRateOrGiftPoints($mc);

                        if ($rate_points['gift_points'] > 0) {
                            $updateSod['intergral_rate'] = 0;
                            $updateSod['total_sod_points'] = $rate_points['gift_points'] * $v['quantity'];
                        }
                        if ($rate_points['intergral_rate'] && $rate_points['gift_points'] == 0) {
                            $updateSod['intergral_rate'] = $rate_points['intergral_rate'];
                            $updateSod['total_sod_points'] = bcmul($v['total_sod_price'], $rate_points['intergral_rate'], 3);
                        }
                    }
                    
                    $total_points += (float)($updateSod['total_sod_points'] ?? 0);
                    if ($v['g_type'] != 1 && isset($v['gmg_id']) && $v['gmg_id']) {
                        $flag[] = $this->setGoodsMultipleGoodsDec(['gmg_id' => $v['gmg_id']], 'stock');
                        actionLog($this->getLS(), '减固定组合商品酒店库存');
                    }
                    if ($v['g_type'] == 1) {
                        $wcLocalOutGoods = $this->resolveWcLocalOutGoodsItems($v, $mc);
                        if ($wcLocalOutGoods) {
                            foreach ($wcLocalOutGoods as $item) {
                                $outArr[$v['channel_position']][] = $item;
                            }
                        } else {
                            $dc = [
                                "channel_code" => $v['channel_code'],
                                "quantity" => $v['quantity'],
                                "is_gift" => $v['is_gift'] ?? 2,
                                "out_port" => $v['out_port'] ?? 1,
                            ];
                            $outArr[$v['channel_position']][] = $dc;
                        }
                    }
                    if ($v['g_type'] == 3) {
                        // 获取核销码
                        $updateSod['checkOff_code'] = $this->getDetailsCheckOffCode();
                    }
                    $this->updateSaleOrdersDetails($updateSod);
                }
            }

            if ($total_points) {
                $this->order['total_points'] = $total_points;
                // 因为存在不同子订单   不同积分兑换比例情况，order表不做intergral_rate的记录，只记录到自订单表
                // $this->order['intergral_rate'] = $final_intergral_rate;  
            }
            $contentArr = $this->buildLegacyMainFromOutGoods($outArr);

            $content = [
                "msgType" => "outGoods",
                "trade_no" => $this->order['trade_no'],
                "main" => $contentArr,
                "outGoods" => $outArr,
                "order_points" => $this->order['total_points'] ?? 0
            ];
            $result = $this->sendToMachine(['machine_id' => $this->order['machine_id']], 'outGoods', $content);
            actionLog(@obj2arr($result), 'AfterOrderPaymentTrait下发数据结果');
            $this->order['out_status'] = 2;
            $this->addOrderOutStatusCallback('ready');
            return true;
        }
        return $this->r(100, $this->lang("VOutGoods.details_no_data"));
    }

    /**
     * 微程实物商品(type=5)和组合商品(type=11)按本机实际货道出货。
     */
    protected function resolveWcLocalOutGoodsItems($detail, array $mc): array
    {
        // normalize $detail to array when an object or model is passed
        if (is_object($detail)) {
            $detail = method_exists($detail, 'toArray') ? $detail->toArray() : (array)$detail;
        } elseif (!is_array($detail)) {
            $detail = (array)$detail;
        }

        // ensure $mc is array
        if (is_object($mc)) {
            $mc = method_exists($mc, 'toArray') ? $mc->toArray() : (array)$mc;
        }
        if (empty($mc['out_no'])) {
            return [];
        }

        $wcGoods = $this->getWcGoodsFind(['no' => $mc['out_no']]);
        if (!$wcGoods) {
            return [];
        }
        $wcGoods = is_array($wcGoods) ? $wcGoods : obj2arr($wcGoods);
        $wcGoodsType = intval($wcGoods['type'] ?? 0);
        if (!in_array($wcGoodsType, [5, 11], true)) {
            return [];
        }

        $localGoods = [];
        if (!empty($detail['wc_goods_no'])) {
            $decoded = json_decode($detail['wc_goods_no'], true);
            if (is_array($decoded)) {
                $localGoods = array_values($decoded);
            } else {
                actionLog([
                    'sod_id' => $detail['sod_id'] ?? 0,
                    'wc_goods_no' => $detail['wc_goods_no'],
                ], 'wc_goods_no JSON parse failed');
            }
        }

        if (!$localGoods) {
            $localWhere = ['out_no' => $mc['out_no']];
            if ($wcGoodsType === 5) {
                $detailGId = intval($detail['g_id'] ?? 0);
                if ($detailGId > 0 && $detailGId !== 9999) {
                    $localWhere['g_id'] = $detailGId;
                } elseif (!empty($detail['sku'])) {
                    $localWhere['sku'] = $detail['sku'];
                } elseif (!empty($detail['bar_code'])) {
                    $localWhere['bar_code'] = $detail['bar_code'];
                }
            }
            $wcGoodsLocal = $this->getWcGoodsLocalList($localWhere);
            if ($wcGoodsLocal) {
                $localGoods = $wcGoodsLocal->toArray();
            }
            if ($wcGoodsType === 5 && count($localGoods) > 1) {
                actionLog([
                    'sod_id' => $detail['sod_id'] ?? 0,
                    'out_no' => $mc['out_no'],
                    'local_count' => count($localGoods),
                ], '微程实物商品匹配到多个本地商品，已跳过实际货道兜底');
                return [];
            }
        }

        $items = [];
        foreach ($localGoods as $local) {
            $needLocalOutGoods = intval($local['need_local_out_goods'] ?? 1);
            if ($needLocalOutGoods !== 1) {
                continue;
            }

            $channelCode = trim((string)($local['real_channel_code'] ?? ''));
            if ($channelCode === '' || $channelCode === 'Z10') {
                $gId = intval($local['g_id'] ?? 0);
                if ($gId > 0 && $gId !== 9999) {
                    $machineChannel = $this->getMachineChannelFind([
                        'g_id' => $gId,
                        'm_id' => intval($this->order['m_id'] ?? 0),
                    ], 'channel_code');
                    if ($machineChannel) {
                        $machineChannel = is_array($machineChannel) ? $machineChannel : obj2arr($machineChannel);
                        $channelCode = trim((string)($machineChannel['channel_code'] ?? ''));
                    }
                }
            }

            if ($channelCode === '' || $channelCode === 'Z10') {
                actionLog([
                    'sod_id' => $detail['sod_id'] ?? 0,
                    'out_no' => $mc['out_no'],
                    'local_no' => $local['no'] ?? '',
                    'g_id' => $local['g_id'] ?? 0,
                ], '微程商品未匹配到实际货道');
                continue;
            }

            $items[] = [
                "channel_code" => $channelCode,
                "quantity" => intval($local['quantity'] ?? ($detail['quantity'] ?? 1)),
                "is_gift" => $detail['is_gift'] ?? 2,
                "out_port" => $detail['out_port'] ?? 1,
            ];
        }

        return $items;
    }

    protected function buildLegacyMainFromOutGoods(array $outArr): array
    {
        $main = [];
        foreach ($outArr as $position => $items) {
            foreach ($items as $item) {
                $channelCode = $item['channel_code'] ?? '';
                if ($channelCode === '') {
                    continue;
                }
                $main[$position][] = [
                    $channelCode,
                    intval($item['quantity'] ?? 1),
                ];
            }
        }
        return $main;
    }

    /**
     * 结算收益
     * @param string $status 分润成功时不能传参，分润失败传3
     * @return int
     */
    protected function settlementRevenue($status = '')
    {
        $service = new RevenueSettlementService();
        if ($status) {
            return $service->markPaymentFailed($this->order['order_id']);
        }
        return $service->handlePaymentSuccess($this->order['order_id'], $this->order['pay_time'] ?? time());
    }

    /**
     * 取消待支付的新分账订单
     * 只处理 status=0 的待支付记录，已结算/失败/已取消记录保持原样。
     */
    protected function cancelPendingRevenueOrders()
    {
        if (empty($this->order['order_id'])) return true;
        $this->clearPendingRevenueCouponInfo();
        Db::name('revenue_order')
            ->where(['order_id' => $this->order['order_id'], 'status' => 0])
            ->update(['status' => 4, 'update_time' => time()]);
        actionLog($this->getLS(), '取消待支付新分账订单SQL');
        return true;
    }

    /**
     * 清理待支付订单上的分账优惠券快照，只影响分账优惠券字段。
     */
    protected function clearPendingRevenueCouponInfo()
    {
        $orderId = intval($this->order['order_id']);
        if ($orderId <= 0) return true;

        $hasRevenueCoupon = trim(strval($this->order['revenue_coupon_code'] ?? '')) !== '';
        if (!$hasRevenueCoupon) {
            $hasRevenueCoupon = Db::name('revenue_order')
                ->where(['order_id' => $orderId, 'status' => 0, 'rule_mode' => 5])
                ->count() > 0;
        }
        if (!$hasRevenueCoupon) return true;

        $update = [
            'revenue_coupon_code' => '',
            'revenue_coupon_discount_type' => 0,
            'revenue_coupon_discount_value' => 0,
            'revenue_coupon_discount_amount' => 0,
            'update_time' => time(),
        ];
        Db::name('sale_orders')->where(['order_id' => $orderId])->update($update);
        foreach ($update as $field => $value) {
            $this->order[$field] = $value;
        }
        actionLog(['order_id' => $orderId], '清理待支付订单分账优惠券信息');
        return true;
    }

    /**
     * 发送销售通知
     */
    private function sendNotice()
    {
        try {
            // $this->noticeSendData = [
            //     "ao_id" => $this->machine['ao_id'],
            //     "m_id" => $this->machine['m_id'],
            //     "templateType" => "sale",
            //     "replaceData" => [
            //         "machine_id" => $this->machine['machine_id'],
            //         "machine_name" => $this->machine['machine_name'],
            //         "trade_no" => $this->order['trade_no'],
            //         "money" => number_format($this->order['total_price'], 2, '.', ','),
            //         "now" => date('Y-m-d H:i:s'),
            //         "error_info" => "订单完成",
            //         "error_code" => "订单完成",
            //     ]
            // ];
            
            //交易成功通知
            $checkPrice = round(round($this->order['total_price'], 2) * 100);
            if($checkPrice > 1){
                $this->noticeSendData = [
                    "ao_id" => $this->machine['ao_id'],
                    "m_id" => $this->machine['m_id'],
                    "templateType" => "payment_success",
                    "replaceData" => [
                        "machine_id" => $this->machine['machine_id'],
                        "machine_name" => mb_substr($this->machine['machine_name'], 0, 20, 'UTF-8'),
                        "trade_no" => $this->order['trade_no'],
                        "total_price" => number_format($this->order['total_price'], 2, '.', ','),
                        "pay_time" => $this->order['pay_time'] ? date('Y-m-d H:i:s', $this->order['pay_time']) : date('Y-m-d H:i:s'),
                    ]
                ];
                $result = @$this->noticeSend();
                actionLog($result, '发送销售通知结果');
            }else{
                actionLog([], '测试数据不发送销售通知');
            }

        } catch (\Exception $e) {
            actionLog("发送销售通知异常");
            actionException($e, 1);
        }
    }
    /**
     * 支付失败处理
     */

    /**
     * 支付失败
     * @return mixed
     */
    public function paymentFailed()
    {
        $this->order['pay_status'] = 4;
        $this->order['pay_time'] = time();
        $this->handleHotel(2);
        $this->cancelPendingRevenueOrders();
        $this->sendToMachine(['machine_id' => $this->order['machine_id']], 'payFail', ['trade_no' => $this->order['trade_no']]);
        return $this->updateSaleOrders($this->order);
    }

    /**
     * 支付失败
     * @return mixed
     */
    public function paymentError()
    {
        $this->order['pay_status'] = 4;
        $this->order['pay_time'] = time();
        $this->handleHotel(2);
        $this->cancelPendingRevenueOrders();
        $this->sendToMachine(['machine_id' => $this->order['machine_id']], 'payError', ['trade_no' => $this->order['trade_no']]);
        return $this->updateSaleOrders($this->order);
    }

    /**
     * 处理订单酒店数据
     * @param int $status 1：支付成功，2：支付失败
     * @return mixed
     */
    public function handleHotel($status)
    {
        // 有酒店数据
        if ($this->order['has_hotel'] == 1) {
            $sh = $this->getSaleHotelFind(['order_id' => $this->order['order_id']]);
            if ($sh) {
                $sh = $sh->toArray();
                actionLog($sh, '酒店数据');
                $updateSh['sh_id'] = $sh['sh_id'];
                $updateSh['pay_time'] = time();
                $updateSh['pay_status'] = ($status == 1 ? 3 : 4);
                $updateSh['pay_amount'] = 0;
                $updateSh['create_status'] = 1;
                // 自营酒店
                if ($sh['hotelFrom'] == 2 && $status == 1) {
                    if ($sh['gmg_id']) {
                        $flag[] = $this->setGoodsMultipleGoodsDec(['gmg_id' => $sh['gmg_id']], 'stock');
                        actionLog($this->getLS(), '减固定组合商品酒店库存');
                    }
                    //                    $updateSh['checkOff_code'] = $this->getHotelCheckOffCode();
                    //                    $updateSh['create_status'] = 2;
                    //                    $updateSh['reservation_status'] = 2;
                    //                    // 发送酒店预订通知
                    //                    $smsParam = [
                    //                        $updateSh['checkOff_code'],
                    //                    ];
                    //                    $phoneNumber = [
                    //                        $this->order['mobile'],
                    //                    ];
                    //                    $result = TencentCloud::sendSms($smsParam,$phoneNumber);
                    //                    actionLog($result,'预订酒店发送短信通知');
                }
                if ($sh['hotelFrom'] == 1 && $this->order['pay_type'] <> 5) {
                    $params = [
                        "tradeNo" => $this->order['out_trade_no'],
                        "payStatus" => $status,
                    ];
                    actionLog($params, '支付结果通知丽呈小程序');
                    $result = Trip::order()->payNotify($params);
                    $result = json2arr($result);
                    actionLog($result, '支付结果通知丽呈小程序结果');
                    if (isset($result['result']['orderStatus']) && $result['result']['orderStatus'] == 1) {
                        $updateSh['create_status'] = 1;
                    } else {
                        $updateSh['create_status'] = 2;
                    }
                }
                $updateResult = $this->updateSaleHotel($updateSh);
                actionLog($this->getLS(), '修改订单酒店数据');
                return $updateResult;
            }
        }
    }
}
