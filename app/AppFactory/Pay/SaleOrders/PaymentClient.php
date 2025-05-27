<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/26
 * Time: 10:06
 */

namespace app\AppFactory\Pay\SaleOrders;


use app\AppFactory\Kernel\Support\AuthCode;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\AliPayTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Kernel\Traits\Payment\TripPay;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelNightlyTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Pay\PayBaseClient;

class PaymentClient extends PayBaseClient
{
    use MachineTrait,
        StrategyPayeeTrait,StrategyIncomeTrait,
        StrategyMachineTrait,
        WxPayTrait,AliPayTrait,JdCashierTrait,TripPay,
        BeforeOrderPaymentTrait,
        UserTrait,
        SaleHotelTrait,SaleHotelNightlyTrait,
        SaleOrdersRevenueTrait;
    use AfterOrderPaymentTrait;
    use MachineMqRecordTrait;
    use ActivityFdUsedTrait;

    public $machine;
    public $strategyPayee;
    public $returnData = [
        "paymentUrlLink" => "",
        "qrCodeLink" => "",
        "order" => [],
        "result" => "",
    ];

    /**
     * @var array 支付类型
     */
    protected $paymentType = [
        "1" => "wxPay",
        "2" => "aliPay",
        "3" => "tlPay",
        "4" => "jdPay",
        "5" => "tripPay",
    ];

    protected $cancelType = [
        "1" => "wxCancel",
        "2" => "aliCancel",
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
     * 发起订单支付
     * @return array|string
     */
    public function orderPay()
    {
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
        actionLog($this->machine,'发起支付设备数据');
        $paymentType = 1;

        // 订单金额大于0才能执行分润，生成分润记录
        if ($this->order['total_price'] > 0) {
            $flag[] = $this->countIncome();
        }


        // 反扫支付二维码
        if (isset($this->data['authCode']) && $this->order['pay_method'] == 2) {
            $this->data['authCode'] = str_replace("Num Lock",'',$this->data['authCode']);
            $paymentType = AuthCode::getCodePayee($this->data['authCode']);
            if (!$paymentType) return $this->rFail($this->lang("VOrderPay.unKnow_auth_code"));
            if (in_array($this->order['pay_type'],[1,2]) && $paymentType != $this->order['pay_type'])
                return $this->rFail($this->lang("VOrderPay.auth_code_not_match_pay_type"));
            $this->order['pay_code'] = $this->data['authCode'];
        }

        $where['sm.s_type'] = 1;
        $where['sp.status'] = 1;
        $where['sp.payee_type'] = $this->order['pay_type'];
        $where['sm.m_id'] = $this->order['m_id'];
        $this->strategyPayee = $this->getStrategyPayeeContent($where,'sp.*','');
        if (!is_array($this->strategyPayee)) return $this->strategyPayee;
        if (!in_array($this->strategyPayee['payee_type'],array_keys($this->paymentType))) {
            return $this->rFail($this->lang("VOrderPay.unKnow_pay_type"));
        }
        if ($this->strategyPayee['payee_type'] == 3) $this->payType = $this->tlPayType[$paymentType];
        if ($this->strategyPayee['payee_type'] == 4) $this->payType = $this->jdPayType[$paymentType];
        actionLog($this->strategyPayee,'收款配置数据');



        $this->order['pay_type'] = $this->strategyPayee['payee_type'];
        $this->order['sp_id'] = $this->strategyPayee['sp_id'];
        $uOrder = $this->updateSaleOrders($this->order,[],['pay_code',"pay_method",'pay_type','sp_id']);
        if ($uOrder) {
            actionLog($this->getLS(), '修改订单支付状态信息');
            $func_name = $this->paymentType[$this->strategyPayee['payee_type']];
            $result = $this->$func_name();
            return $result;
        }
        return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
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
            if ($this->order['sp_id']) {
                if ($this->order['pay_type'] != 5) {
                    $where['sp_id'] = $this->order['sp_id'];
                    $where['sm.s_type'] = 1;
                    $where['sp.status'] = 1;
                    $where['sp.payee_type'] = $this->order['pay_type'];
                    $where['sm.m_id'] = $this->order['m_id'];
                    $this->strategyPayee = $this->getStrategyPayeeContent($where, 'sp.*', '');
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
                $uOrder = $this->updateSaleOrders($this->order, [], ['pay_status']);
                if ($uOrder && $this->order['pay_type'] != 5) {
                    $func_name = $this->cancelType[$this->strategyPayee['payee_type']];
                    if (method_exists($this,$func_name)) {
                        $result = $this->$func_name();
                        return $result;
                    }
                }
                return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail"));
            }
            return $this->rSuccess();
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}