<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:06
 */

namespace app\AppFactory\Kernel\Traits\Payment;


use app\AppFactory\Kernel\Support\Validate\Pay\VJdCashierPay;
use Jd\Jd;
use Jd\Payment\Application;

trait JdCashierTrait
{
    /**
     * @var Application
     */
    protected $jdApp;

    protected $jdPaymentMethod = [
        "41" => "jdUrlLink",
        "42" => "jdScanQr",
        "43" => "jdJsApi",
    ];

    public function jdPay()
    {
        if (!in_array($this->order['pay_method'],array_keys($this->jdPaymentMethod)))
            return $this->rFail($this->lang("VJdCashier.pay_type_not_in_scope"));
        try {
            validate(VJdCashierPay::class)->scene('jdPay')->check($this->strategyPayee);
        } catch (\Exception $e) {
            return $this->rValidate($this->lang($e->getMessage()));
        }
        $this->jdApp = Jd::payment($this->strategyPayee);
        $func_name = $this->jdPaymentMethod[$this->order['pay_method']];
        return $this->$func_name();
    }

    /**
     * 京东反扫支付
     * @param array $order
     * @param string $authCode
     * @param string $paySource
     * @param string $locationE
     * @param string $locationN
     * @return bool|string
     */
    protected function jdScanQr($paySource = 'WX', $locationE = '22.5639220000', $locationN = '113.3978830000')
    {
        $extraMap = ['o_id' => $this->order['order_id']];
        $tradeNo = $this->order['trade_no'];

        $terminalInfo = [
            'locationE' => $locationE,
            'locationN' => $locationN,
            'encrypt_rand_num' => substr($this->order['pay_code'], strlen($this->order['pay_code']) - 6, 6),
        ];
        $notify =  $this->getUrl("/pay/notify.jd_cashier/orderNotify");
        $params = [
            //商户号
            "version" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "authCode" => $this->order['pay_code'],
            "bankType" => 'WX', // 微信：WX，小程序：WX_XCX，支付宝：ALIPAY，京东：JD，银联：UNIONPAY
            "requestNum" => $tradeNo, // 商户系统内部订单号(商户系统内唯一)
            "orderAmount" => $this->order['total_price'],
            "callbackUrl" => $notify,
            "subOrderType" => 'NORMAL', // 交易子类型普通交易NORMAL，分账交易：LEDGER，默认值：NORMAL
            "orderType" => 'SALES', // 消费：SALES，退款：REFUND
            "payType" => 'PASSIVE', // 主扫：ACTIVE，被扫：PASSIVE
            "bussinessType" => 'QRCODE_TRAD', // 固定值：QRCODE_TRAD
            "payModel" => 'ONCE',  // 一单一付：ONCE，一单多付：MORE
            "source" => 'API',
            "paySource" => $paySource,
            "extraInfo" => json_encode($extraMap),
            "terminalInfo" => json_decode(json_encode($terminalInfo)),
        ];
        $list = $this->getBillList();
        actionLog($list,'分账列表');
        if ($list) {
            $LedgerRequest = [
                'ledgerType' => 'FIXED',
                'ledgerFeeAssume' => 'RECEIVER',
                'list' => $list,
            ];
            $params['LedgerRequest'] = $LedgerRequest;
            $params['subOrderType'] = 'LEDGER';
        }
        actionLog($params, '京东收银反扫请求参数');
        $result = $this->jdApp->order->microPay($params);
        actionLog($result, '京东收银反扫返回结果');

        if (isset($result['openId']) && $result['openId'] && $result['openId'] != "000000") {
            $user = $this->getUserFind(['openid' => $result['openId']]);
            if ($user) {
                $this->order['user_id'] = $user['user_id'];
                $this->order['user_name'] = $user['name'];
            } else {
                $insert['openid'] = $result['openId'];
                $insert['type'] = 2;
                if (substr($result['openId'],0,4) == 2088) $insert['type'] = 3;
                $this->order['user_id'] = $this->addUser($insert);
            }
        }
        return $this->rAction($result);
    }

    /**
     * 获取支付地址，可跳转或生成支付二维码
     * @return bool|string
     */
    protected function jdUrlLink()
    {
        $extraMap = ['o_id' => $this->order['order_id']];
        $tradeNo = $this->order['trade_no'];
        $notify =  $this->getUrl("/pay/notify.jd_cashier/orderNotify");
        $orderAmount = round($this->order['total_price'],2);
        $params = [
            //商户号
            "version" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "requestNum" => $tradeNo,
            "orderAmount" => "$orderAmount",
            "callbackUrl" => $notify,
            "subOrderType" => 'NORMAL',
            "orderType" => 'SALES', // 消费：SALES，退款：REFUND
            "timeExpire" => date('Y-m-d H:i:s', time() + 300), // 过期时间，5分钟内
            "bussinessType" => 'QRCODE_TRAD', // 固定值：QRCODE_TRAD
            "payModel" => 'ONCE',  // 一单一付：ONCE，一单多付：MORE
            "source" => 'API',
            "extraInfo" => json_encode($extraMap)
        ];
        $list = $this->getBillList();
        actionLog($list,'分账列表');
        if ($list) {
            $LedgerRequest = [
                'ledgerType' => 'FIXED',
                'ledgerFeeAssume' => 'RECEIVER',
                'list' => $list,
            ];
            $params['LedgerRequest'] = $LedgerRequest;
            $params['subOrderType'] = 'LEDGER';
        }
//        dump($this->strategyPayee);
//        dump($params);
//        dump(json_encode($params));
        actionLog($params, '京东收银生成支付二维码地址请求参数');
        $result = $this->jdApp->order->qrCodeUrl($params);
        actionLog($result, '京东收银生成支付二维码地址返回结果');
        if (isset($result['code']) && $result['code'] == 'success') {
            return $this->r(200, $this->lang("init_payment_success"), ['paymentUrlLink' => $result['url'], 'order' => $this->order,'result' => $result]);
        }
        $msg = '';
        if (isset($result['error']['errorMsg'])) $msg .= $result['error']['errorMsg'] . "；";
        if (isset($result['code'])) $msg .= $result['code'] . "；";
        if (isset($result['msg'])) $msg .= $result['msg'] . "；";
        return $this->r(100, $this->lang("init_payment_fail") . '：' . $msg, $result);
    }
    
    /**
     * JSAPI
     * @return mixed
     */
    protected function jdJsApi()
    {
        $type = WxOrAli();
        $paySource = $type == 1 ? "WX_XCX" : "ALIPAY";
        $paySource == 'WX_XCX' ? $openid = $this->getUserValue(['user_id' => $this->order['user_id']],'openid') : $openid = '';
        $extraMap = ['o_id' => $this->order['order_id']];
        $tradeNo = $this->order['trade_no'];
        $notify =  $this->getUrl("/pay/notify.jd_cashier/orderNotify");
        $params = [
            //商户号
            "version" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "authCode" => $openid,
            "bankType" => $paySource, // 微信：WX，小程序：WX_XCX，支付宝：ALIPAY，京东：JD，银联：UNIONPAY
            "requestNum" => $tradeNo, // 商户系统内部订单号(商户系统内唯一)
            "orderAmount" => $this->order['total_price'],
            "callbackUrl" => $notify,
            "subOrderType" => 'NORMAL', // 交易子类型普通交易NORMAL，分账交易：LEDGER，默认值：NORMAL
            "orderType" => 'SALES', // 消费：SALES，退款：REFUND
            "payType" => 'ACTIVE', // 主扫：ACTIVE，被扫：PASSIVE
            "bussinessType" => 'QRCODE_TRAD', // 固定值：QRCODE_TRAD
            "payModel" => 'ONCE',  // 一单一付：ONCE，一单多付：MORE
            "source" => 'API',
            "paySource" => $paySource,
            "extraInfo" => json_encode($extraMap),
        ];
        $list = $this->getBillList();
        actionLog($list,'分账列表');
        if ($list) {
            $LedgerRequest = [
                'ledgerType' => 'FIXED',
                'ledgerFeeAssume' => 'RECEIVER',
                'list' => $list,
            ];
            $params['LedgerRequest'] = $LedgerRequest;
            $params['subOrderType'] = 'LEDGER';
        }
        actionLog($params, '京东收银JSAPI请求参数');
        $app = Jd::payment($this->strategyPayee);
        $result = $app->order->jsApi($params);
        actionLog($result, '京东收银JSAPI返回结果');
        if (isset($result['code']) && $result['code'] == 'success') {
            return $this->r(200, $this->lang("init_payment_success"), ['paymentUrlLink' => $result['bankRequest'], 'order' => $this->order,'result' => $result]);
        }
        $msg = '';
        if (isset($result['error']['errorMsg'])) $msg .= $result['error']['errorMsg'] . "；";
        if (isset($result['code'])) $msg .= $result['code'] . "；";
        if (isset($result['msg'])) $msg .= $result['msg'] . "；";
        return $this->r(100, $this->lang("init_payment_fail") . '：' . $msg, $result);
    }

    /**
     * 获取分账数据
     * @return array
     */
    public function getBillList()
    {
        $billList = [];
        if (isset($this->strategyPayee['bill_account'])) {
            $revenue = $this->getSaleOrdersRevenueList(['order_id' => $this->order['order_id'], 'revenue_type' => 4]);
            if ($revenue) {
                $totalAmount = 0;
                foreach ($revenue as $key => $value) {
                    $billAccount = $this->getAuthManagerValue(['manager_id' => $value['beneficiary']], 'bill_account');
                    $bill['customerNum'] = $billAccount;
                    $amount = $value['income_amount'];
                    $bill['amount'] = "$amount";
                    $billList[] = $bill;
                    $totalAmount = bcadd($amount, $totalAmount, 3);
                }
                $amount = bcsub($this->order['total_price'], $totalAmount, 2);
                $billList[] = [
                    'amount' => "$amount",
                    'customerNum' => $this->strategyPayee['bill_account'],
                ];
            }
        }
        return $billList;
    }

    /**
     * 退款申请
     * @return array|string
     */
    public function jdRefund()
    {
        $this->getUrl("/pay/notify.jd_cashier/refundNotify");
        $this->totalRefundMoney = number_format($this->totalRefundMoney,2);
        $params = [
            //商户号
            "requestVersion" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "requestNum" => $this->order['trade_no'], // 商户系统内部订单号(商户系统内唯一)
            "notifyUrl" => $this->getUrl("/pay/notify.jd_cashier/refundNotify"),
            "refundRequestNum" => $this->refundTradeNo, // 退款单号
            "refundPartAmount" => "$this->totalRefundMoney", // 退款金额
        ];
        if ($this->billList) $params['list'] = $this->billList;
        $this->jdApp = Jd::payment($this->strategyPayee);
        actionLog($params, '退款申请参数');
        $result = $this->jdApp->order->refundByTradeNo($params);
        actionLog($result, '退款申请结果');
        if (isset($result['result']) && $result['result'] !== true) return returnState(100, '退款失败', $result);
        if ($result['refundStatus'] == 'SUCCESS') {
            return $this->rSuccess( '退款成功');
        }
        if ($result['refundStatus'] == 'INIT') {
            return $this->rSuccess( '发起退款成功，正在退款中，请稍后手动查询');
        }
        if ($result['refundStatus'] == 'FAIL') {
            $this->refundFail();
            return $this->rFail( '退款失败');
        }
        return $this->r( 100,'未识别状态：' . $result['resultStatus'], $result);
    }

    /**
     * 撤销订单
     * @return mixed
     */
    public function jdCancel()
    {
        $params = [
            //商户号
            "version" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "requestNum" => $this->order['trade_no'],
        ];
        $this->jdApp = Jd::payment($this->strategyPayee);
        actionLog($params, '京东收银撤销订单请求参数');
        $result = $this->jdApp->order->cancel($params);
        actionLog($result, '京东收银撤销订单返回结果');
        if (isset($result['code']) && $result['code'] == 'success') {
            return $this->r(200, $this->lang("cancel_payment_success"));
        }
        $msg = '';
        if (isset($result['error']['errorMsg'])) $msg .= $result['error']['errorMsg'] . "；";
        if (isset($result['code'])) $msg .= $result['code'] . "；";
        if (isset($result['msg'])) $msg .= $result['msg'] . "；";
        return $this->r(100, $this->lang("cancel_payment_fail") . '：' . $msg, $result);
    }

}