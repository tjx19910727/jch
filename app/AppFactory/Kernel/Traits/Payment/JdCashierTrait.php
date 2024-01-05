<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:06
 */

namespace app\AppFactory\Kernel\Traits\Payment;


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
        if (!in_array($this->order['payment_method'],array_keys($this->jdPaymentMethod)))
            return $this->rFail("支付方式不在允许范围内");
        $this->order['sp_id'] = $this->strategyPayee['sp_id'];
        $this->jdApp = Jd::payment($this->strategyPayee);
        $func_name = $this->jdPaymentMethod[$this->order['payment_method']];
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
    public function jdScanQr($paySource = 'WX', $locationE = '22.5639220000', $locationN = '113.3978830000')
    {
        $extraMap = ['o_id' => $this->order['order_id']];
        $tradeNo = $this->order['order_trade_no'];

        $terminalInfo = [
            'locationE' => $locationE,
            'locationN' => $locationN,
            'encrypt_rand_num' => substr($this->order['payment_code'], strlen($this->order['payment_code']) - 6, 6),
        ];
        $notify =  $this->getUrl("/http/pay.jd_cashier/orderNotify");
        $params = [
            //商户号
            "version" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "authCode" => $this->order['payment_code'],
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
        return $result;
    }

    /**
     * 获取支付地址，可跳转或生成支付二维码
     * @return bool|string
     */
    public function jdUrlLink()
    {
        $extraMap = ['o_id' => $this->order['order_id']];
        $tradeNo = $this->order['order_trade_no'];
        $notify =  $this->getUrl("/http/pay.jd_cashier/orderNotify");
        $params = [
            //商户号
            "version" => 'V4.0',
            "customerNum" => $this->strategyPayee['customerNum'],
            "requestNum" => $tradeNo,
            "orderAmount" => $this->order['total_price'],
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
        actionLog($params, '京东收银生成支付二维码地址请求参数');
        $result = $this->jdApp->order->qrCodeUrl($params);
        actionLog($result, '京东收银生成支付二维码地址返回结果');
        return $result;
    }


    /**
     * JSAPI
     * @return mixed
     */
    public function jdJsApi()
    {
        $type = WxOrAli();
        $paySource = $type == 1 ? "WX_XCX" : "ALIPAY";
        $paySource == 'WX_XCX' ? $openid = $this->getUserValue(['user_id' => $this->order['user_id']],'openid') : $openid = '';
        $extraMap = ['o_id' => $this->order['order_id']];
        $tradeNo = $this->order['order_trade_no'];
        $notify =  $this->getUrl("/http/pay.jd_cashier/orderNotify");
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
            return $this->r(200, '发起支付成功', ['paymentUrlLink' => $result['bankRequest'], 'order' => $this->order,'result' => $result]);
        }
        $msg = '';
        if (isset($result['error']['errorMsg'])) $msg .= $result['error']['errorMsg'] . "；";
        if (isset($result['code'])) $msg .= $result['code'] . "；";
        if (isset($result['msg'])) $msg .= $result['msg'] . "；";
        return $this->r(100, '京东收银发起支付失败：' . $msg, $result);
    }

    /**
     * 获取分账数据
     * @return array
     */
    public function getBillList()
    {
        $billList = [];
        $revenue = $this->getSaleOrdersRevenueList(['order_id' => $this->order['order_id'],'revenue_type' => 4]);
        if ($revenue) {
            $totalAmount = 0;
            foreach ($revenue as $key => $value) {
                $billAccount = $this->getAuthManagerValue(['manager_id' => $value['beneficiary']],'bill_account');
                $bill['customerNum'] = $billAccount;
                $amount = $value['income_amount'];
                $bill['amount'] = "$amount";
                $billList[] = $bill;
                $totalAmount = bcadd($amount,$totalAmount,3);
            }
            $amount = bcsub($this->order['total_price'],$totalAmount,2);
            $billList[] = [
                'amount' => "$amount",
                'customerNum' => $this->strategyPayee['bill_account'],
            ];
        }
        return $billList;
    }

}