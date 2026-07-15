<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/26
 * Time: 10:06
 */

namespace app\AppFactory\Pay\SaleOrders;


use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeModel;
use app\AppFactory\Kernel\Support\AuthCode;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\AliPayTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BalancePayTrait;
use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Kernel\Traits\Payment\TripPay;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\Payment\MallPointsPayTrait;
use app\AppFactory\Kernel\Traits\Mall\MallMachineTrait;
use app\AppFactory\Kernel\Traits\Mall\MallTrait;
use app\AppFactory\Kernel\Traits\Mall\MallRequestLogsTrait;

use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelNightlyTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Pay\PayBaseClient;

class PaymentClient extends PayBaseClient
{
    use MachineTrait,
        StrategyPayeeTrait,
        StrategyMachineTrait,
        WxPayTrait,AliPayTrait,JdCashierTrait,TripPay,
        MallPointsPayTrait,BalancePayTrait,MallMachineTrait,MallTrait,MallRequestLogsTrait,
        BeforeOrderPaymentTrait,
        UserTrait,
        SaleHotelTrait,SaleHotelNightlyTrait;
    use AfterOrderPaymentTrait;
    use MachineMqRecordTrait;
    use ActivityCouponUsedTrait;
    use ActivityFdUsedTrait;

    public $machine;
    public $strategyPayee;
    public $returnData = [
        "paymentUrlLink" => "",
        "qrCodeLink" => "",
        "order" => [],
        "result" => "",
        "pay_required" => true,
        "zero_pay" => false,
        "next_action" => "pay",
    ];

    /**
     * @var array 支付类型
     */
    protected $paymentType = [
        "1" => "wxPay",
        "11" => "wxPay",
        "12" => "wxPay",
        "2" => "aliPay",
        "21" => "aliPay",
        "22" => "aliPay",
        "3" => "tlPay",
        "4" => "jdPay",
        "5" => "tripPay",
        "9" => "mallPointsPay",
        "20" => "balancePay",
    ];

    protected $cancelType = [
        "1" => "wxCancel",
        "11" => "wxCancel",
        "12" => "wxCancel",
        "2" => "aliCancel",
        "21" => "aliCancel",
        "22" => "aliCancel",
        "3" => "tlCancel",
        "4" => "jdCancel",
    ];


    /**
     * 通联支付类型
     * @var array
     */
    protected $tlPayType = [
        1 => "W01",
        2 => "A01",
    ];

    /**
     * 京东收银支付
     * @var array
     */
    protected $jdPayType = [
        1 => "WX",
        2 => "ALIPAY",
    ];

    /**
     * 获取兼容的收款策略支付类型（新细分类型回退到主通道）
     * @param int $payType
     * @return int
     */
    protected function getCompatiblePayeeType($payType)
    {
        $payType = intval($payType);
        if (in_array($payType, [11, 12], true)) return 1;
        if (in_array($payType, [21, 22], true)) return 2;
        return $payType;
    }

    /**
     * 发起订单支付
     * @return array|string
     */
    public function orderPay()
    {
        try{
            $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
            if (!$this->order) {
                return $this->rFail($this->lang("VOrderPay.order_no_data"));
            }
            $this->order = $this->order->toArray();
            actionLog($this->order,'发起支付订单数据');
            $this->machine = $this->getMachineFind(['m_id' => $this->order['m_id']]);
            if (!$this->machine) {
                return $this->rFail($this->lang("VOrderPay.machine_no_data"));
            }
            // throw new \Exception('测试支付异常'); // ← 临时加这行
            actionLog($this->machine,'发起支付设备数据');
            $paymentType = 1;

            // 反扫支付二维码
            if (isset($this->data['authCode']) && $this->order['pay_method'] == 2) {
                $this->data['authCode'] = str_replace("Num Lock",'',$this->data['authCode']);
                $paymentType = AuthCode::getCodePayee($this->data['authCode']);
                if (!$paymentType) return $this->rFail($this->lang("VOrderPay.unKnow_auth_code"));
                if (in_array($this->order['pay_type'], [1, 2, 11, 12, 21, 22], true)) {
                    $expectPayType = $this->order['pay_type'];
                    if (in_array($expectPayType, [11, 12], true)) $expectPayType = 1;
                    if (in_array($expectPayType, [21, 22], true)) $expectPayType = 2;
                    if ($paymentType != $expectPayType) {
                        return $this->rFail($this->lang("VOrderPay.auth_code_not_match_pay_type"));
                    }
                }
                $this->order['pay_code'] = $this->data['authCode'];
            }
            //余额支付
            if ($this->order['pay_type'] == 20 && !empty($this->data['card_no'])) {
                $this->order['pay_code'] = $this->data['card_no'];
            }


            $where['sm.s_type'] = 1;
            $where['sp.status'] = 1;
            $where['sp.payee_type'] = $this->order['pay_type'];
            $where['sm.m_id'] = $this->order['m_id'];
            if($this->machine['ao_id'] > 18){
                $where['sm.ao_id'] = $this->machine['ao_id'];
            }
            $this->strategyPayee = $this->getStrategyPayeeContent($where,'sp.*','');
            if ((!is_array($this->strategyPayee) || !$this->strategyPayee) && in_array(intval($this->order['pay_type']), [11, 12, 21, 22], true)) {
                $where['sp.payee_type'] = $this->getCompatiblePayeeType($this->order['pay_type']);
                $this->strategyPayee = $this->getStrategyPayeeContent($where,'sp.*','');
            }
            if (!is_array($this->strategyPayee)) return $this->strategyPayee;
            if (!in_array($this->strategyPayee['payee_type'],array_keys($this->paymentType))) {
                return $this->rFail($this->lang("VOrderPay.unKnow_pay_type"));
            }
            if ($this->strategyPayee['payee_type'] == 3) $this->payType = $this->tlPayType[$paymentType];
            if ($this->strategyPayee['payee_type'] == 4) $this->payType = $this->jdPayType[$paymentType];
            actionLog($this->strategyPayee,'收款配置数据');



            if (!in_array(intval($this->order['pay_type']), [11, 12, 21, 22], true)) {
                $this->order['pay_type'] = $this->strategyPayee['payee_type'];
            }
            $this->order['sp_id'] = $this->strategyPayee['sp_id'];
            $isMallPointsExchangeOrder = $this->isMallPointsExchangeOrder($this->order);

            // 新分账须在 sp_id、pay_type 与本次收款策略确定之后执行，保证 revenue_order.sp_id 与当笔支付一致
            if ($this->order['total_price'] > 0) {
                $countIncome = $this->countIncome();
                if (!$countIncome) {
                    return $this->rFail($this->revenueError ?: '生成分账订单失败');
                }
            } elseif (!$isMallPointsExchangeOrder) {
                // 订单金额为0（如优惠券全额抵扣），免支付直接完成
                actionLog(['order_id' => $this->order['order_id'], 'total_price' => $this->order['total_price']], '订单金额为0，执行免支付完成');
                $this->startTrans();
                try {
                    $flagSettlement = $this->settlementRevenue();
                    $flagPayment = $this->paymentSuccessful();
                    $checkFlag = flag_check([$flagSettlement, $flagPayment]);
                    actionLog($checkFlag, '免支付订单完成事务处理结果');
                    $result = $this->checkTrans($checkFlag);
                    $this->returnData['order'] = $this->order;
                    $this->returnData['pay_required'] = false;
                    $this->returnData['zero_pay'] = true;
                    $this->returnData['next_action'] = 'wait_out_goods';
                    $this->returnData['result'] = $result;
                    return $this->r(200, $this->lang("VOrderPay.pay_status3"), $this->returnData);
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionLog($e->getMessage(), '免支付订单处理异常');
                    return $this->rFail($this->lang("VOrderPay.pay_exception"));
                }
            }

            // 优惠后金额小于 0.01 元视为零元订单，跳过第三方支付直接走支付成功流程
            if (!$isMallPointsExchangeOrder && bccomp(strval($this->order['total_price'] ?? 0), '0.01', 2) < 0) {
                $this->order['total_price'] = '0.00';
                $this->order['pay_status'] = 3;
                $this->order['pay_time'] = time();
                $this->order['pay_type'] = 0;
                $this->order['pay_method'] = 1;
                $uOrder = $this->updateSaleOrders($this->order, [], ['pay_code', 'pay_method', 'pay_type', 'sp_id', 'pay_status', 'pay_time']);
                if ($uOrder) {
                    actionLog($this->order, '零元订单直接标记支付成功');
                    $result = $this->paymentSuccessful();
                    $this->returnData['order'] = $this->order;
                    $this->returnData['pay_required'] = false;
                    $this->returnData['zero_pay'] = true;
                    $this->returnData['next_action'] = 'wait_out_goods';
                    $this->returnData['result'] = $result;
                    return $this->r(200, $this->lang("VOrderPay.pay_status3"), $this->returnData);
                }
                return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
            }

            $uOrder = $this->updateSaleOrders($this->order,[],['pay_code',"pay_method",'pay_type','sp_id']);
            if ($uOrder) {
                actionLog($this->getLS(), '修改订单支付状态信息');
                $func_name = $this->paymentType[$this->strategyPayee['payee_type']];
                actionLog([
                    'order_id' => $this->order['order_id'] ?? null,
                    'pay_type' => $this->order['pay_type'] ?? null,
                    'pay_method' => $this->order['pay_method'] ?? null,
                    'sp_id' => $this->order['sp_id'] ?? null,
                    'payee_type' => $this->strategyPayee['payee_type'] ?? null,
                    'dispatch_method' => $func_name,
                ], '支付方法路由分派');
                $result = $this->$func_name();
                return $result;
            }
            return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
        } catch (\Exception $e) {
            actionLog($e->getMessage(), '支付异常');
            if (isset($this->machine)) {
                $this->recordPayError('拉取支付异常，请排查支付配置');
            }
            return $this->rFail($this->lang("VOrderPay.pay_exception"));
        }
    }

    /**
     * 撤销支付
     * @return array|string
     */
    public function cancelPay()
    {

        try {
            $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
            if (!$this->order) {
                return $this->rFail($this->lang("VOrderPay.order_no_data"));
            }
            $this->order = $this->order->toArray();
            if ($this->order['pay_status'] == 3) return $this->rFail($this->lang("VOrderPay.pay_status3"));
            actionLog($this->order, '发起支付订单数据');
            $this->machine = $this->getMachineFind(['m_id' => $this->order['m_id']]);
            if (!$this->machine) {
                return $this->rFail($this->lang("VOrderPay.machine_no_data"));
            }
            if ($this->order['sp_id']) {
                if ($this->order['pay_type'] != 5) {
                    $where['sp_id'] = $this->order['sp_id'];
                    $where['sm.s_type'] = 1;
                    $where['sp.status'] = 1;
                    $where['sp.payee_type'] = $this->order['pay_type'];
                    $where['sm.m_id'] = $this->order['m_id'];
                    if($this->machine['ao_id'] == 19){
                        $where['sm.ao_id'] = $this->order['ao_id'];
                    }
                    $this->strategyPayee = $this->getStrategyPayeeContent($where, 'sp.*', '');
                    if ((!is_array($this->strategyPayee) || !$this->strategyPayee) && in_array(intval($this->order['pay_type']), [11, 12, 21, 22], true)) {
                        $where['sp.payee_type'] = $this->getCompatiblePayeeType($this->order['pay_type']);
                        $this->strategyPayee = $this->getStrategyPayeeContent($where, 'sp.*', '');
                    }
                    if (!is_array($this->strategyPayee)) return $this->strategyPayee;
                    if (!in_array($this->strategyPayee['payee_type'], array_keys($this->cancelType))) {
                        return $this->rFail($this->lang("VOrderPay.unKnow_pay_type"));
                    }
                }
                actionLog($this->strategyPayee, '收款配置数据');
                $this->order['pay_status'] = 5;
                // 清除绑定满减数据
                if ($this->order['fd_id'] > 0) {
                    $this->delActivityFdUsed(['order_id' => $this->order['order_id']]);
                }
                if (!$this->clearCancelPayCouponInfo()) {
                    return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
                }
                $uOrder = $this->updateSaleOrders($this->order, [], ['pay_status']);
                if ($uOrder && $this->order['pay_type'] != 5) {
                    $this->cancelPendingRevenueOrders();
                    $func_name = $this->cancelType[$this->strategyPayee['payee_type']];
                    if (method_exists($this,$func_name)) {
                        $result = $this->$func_name();
                        return $result;
                    }
                }
                if ($uOrder && $this->order['pay_type'] == 5) {
                    $this->cancelPendingRevenueOrders();
                    return $this->rSuccess();
                }
                return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
            }
            if (!$this->clearCancelPayCouponInfo()) {
                return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
            }
            $this->cancelPendingRevenueOrders();
            return $this->rSuccess();
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 取消支付时回滚普通优惠券绑定和订单金额，分账优惠券快照由 cancelPendingRevenueOrders() 清理。
     */
    protected function clearCancelPayCouponInfo()
    {
        if (empty($this->order['coupon_id']) && floatval($this->order['discount_price'] ?? 0) <= 0) {
            return true;
        }

        $orderId = intval($this->order['order_id']);
        $used = $this->getActivityCouponUsedFind(['order_id' => $orderId], 'cu_id,c_id,code_type,original_price');
        if ($used && is_object($used) && method_exists($used, 'toArray')) {
            $used = $used->toArray();
        }
        $originalPrice = $used && floatval($used['original_price'] ?? 0) > 0
            ? $used['original_price']
            : (floatval($this->order['retail_price'] ?? 0) > 0
                ? $this->order['retail_price']
                : bcadd($this->order['total_price'], $this->order['discount_price'], 3));

        $flag = [];
        $details = $this->getSaleOrdersDetailsList(['order_id' => $orderId], 0, 'sod_id,total_sod_price,discount_price');
        if ($details && is_object($details) && method_exists($details, 'toArray')) {
            $details = $details->toArray();
        }
        if ($details) {
            foreach ($details as $detail) {
                $discountPrice = strval($detail['discount_price'] ?? 0);
                if (bccomp($discountPrice, '0', 4) <= 0) continue;
                $flag[] = $this->updateSaleOrdersDetails([
                    'sod_id' => $detail['sod_id'],
                    'total_sod_price' => bcadd(strval($detail['total_sod_price'] ?? 0), $discountPrice, 4),
                    'discount_price' => 0,
                ]);
            }
        }

        $flag[] = $this->updateSaleOrders([
            'order_id' => $orderId,
            'coupon_id' => 0,
            'discount_price' => 0,
            'total_price' => $originalPrice,
            'update_time' => time(),
        ]);

        if ($used) {
            $flag[] = $this->updateActivityCouponUsed([
                'cu_id' => $used['cu_id'],
                'status' => intval($used['code_type']) == 1 ? 1 : 4,
                'order_id' => null,
                'trade_no' => '',
                'm_id' => 0,
                'machine_id' => '',
                'machine_name' => '',
                'original_price' => 0,
                'discount_price' => 0,
                'retail_price' => 0,
            ]);
        }

        $this->order['coupon_id'] = 0;
        $this->order['discount_price'] = 0;
        $this->order['total_price'] = $originalPrice;
        return $this->checkFlag($flag);
    }

    /**
     * 改密码
     * @return array|string
     */
    public function changeCardPwd(){
        try {
            $cardNo = trim((string)($this->data['card_no'] ?? ''));
            $oldPwd = isset($this->data['old_pwd']) ? (string)$this->data['old_pwd'] : '';
            $newPwd = (string)($this->data['new_pwd'] ?? '');
            $confirmPwd = (string)($this->data['confirm_pwd'] ?? '');

            if ($cardNo === '') {
                return $this->rFail('卡号不能为空');
            }
            if ($newPwd !== $confirmPwd) {
                return $this->rFail('两次输入的密码不一致');
            }

            $card = $this->getCardFind(['card_no' => $cardNo], 'card_no,password');
            if (!$card) {
                return $this->rFail('查无会员卡信息');
            }

            $oldPwdEncrypt = md5($oldPwd . config('app.salt').$cardNo);
            $cardPassword = (string)($card['password'] ?? '');
            if ($oldPwdEncrypt !== $cardPassword) {
                return $this->rFail('旧密码错误');
            }

            $result = $this->updateCard([
                'password' => md5($newPwd . config('app.salt').$cardNo),
            ], ['card_no' => $cardNo]);

            if ($result) {
                return $this->rSuccess('修改成功');
            }
            return $this->rFail('修改失败');
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 记录支付异常错误码并发送通知
     * @param string $msg 异常信息
     */
    protected function recordPayError($msg)
    {
        try {
            $machineName = mb_substr($this->machine['machine_name'] ?? '', 0, 20, 'UTF-8');
            $errorMsg = mb_substr($msg, 0, 20, 'UTF-8');

            $insert = [
                "m_id" => $this->machine['m_id'] ?? 0,
                "machine_id" => $this->machine['machine_id'] ?? '',
                "machine_name" => $machineName,
                "address" => $this->machine['address'] ?? '',
                "error_position" => 4,
                "errorCode" => "2100021",
                "remark" => $errorMsg,
                "msg" => $msg,
                "ao_id" => $this->machine['ao_id'] ?? 0,
            ];
            $meId = MachineErrorCodeModel::create($insert)->me_id;

            $this->noticeSendData = [
                "ao_id" => $this->machine['ao_id'] ?? 0,
                "m_id" => $this->machine['m_id'] ?? 0,
                "me_id" => $meId,
                "templateType" => "mFault",
                "replaceData" => [
                    "machine_id" => $this->machine['machine_id'] ?? '',
                    "machine_name" => $machineName,
                    "errorCode" => "2100021",
                    "date" => date("Y年m月d日"),
                    "exceptionDeclaration" => $errorMsg,
                    "error_code" => $errorMsg,
                    "error_time" => date('Y-m-d H:i:s'),
                    "error_info" => "2100021",
                ],
            ];
            actionLog($this->noticeSendData, '发送支付异常通知');
            @$this->noticeSend();
        } catch (\Exception $e) {
            actionException($e);
        }
    }  
}
