<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:05
 */

namespace app\AppFactory\Kernel\Traits\Payment;


use AliPay\Factory;
use AliPay\trade\Application;

trait AliPayTrait
{
    /**
     * @var Application
     */
    protected $aliApp;

    protected $aliPaymentMethod = [
        "21" => "aliMobilePay",
        "22" => "aliScanQr",
    ];

    /**
     * 支付宝支付入口
     * @return array|string
     */
    public function aliPay()
    {
        $this->config = $this->strategyPayee;
        if (!is_array($this->config)) return $this->config;

        $this->config['ali_public_key_path'] = root_path() . "public/" . $this->config['ali_public_key_path'];
        if (!file_exists($this->config['ali_public_key_path'])) return returnState(100, "支付宝公钥文件不存在", $this->config['ali_public_key_path']);
        $this->config['ali_root_cert_path'] = root_path() . "public/" . $this->config['ali_root_cert_path'];
        if (!file_exists($this->config['ali_root_cert_path'])) return returnState(100, "根文件不存在", $this->config['ali_root_cert_path']);
        $this->config['app_public_key_path'] = root_path() . "public/" . $this->config['app_public_key_path'];
        if (!file_exists($this->config['app_public_key_path'])) return returnState(100, "应用公钥文件不存在", $this->config['app_public_key_path']);

        $this->config['isObject'] = false;
        $url = $this->getUrl('/http/pay.ali/paymentNotify');
        $this->config['notifyUrl'] = $url;
        $this->aliApp = Factory::trade($this->config);
        $this->order['sp_id'] = $this->config['sp_id'];
        $func_name = $this->aliPaymentMethod[$this->order['payment_method']];
        return $this->$func_name();

    }

    /**
     * 手机发起支付宝支付，返回Form表单格式
     * @return mixed
     */
    public function aliMobilePay()
    {
        $details = $this->getSaleOrdersDetailsColumn(['order_id' => $this->order['order_id']], 'goods_name');
        $goodsName = implode(",", $details);
        $data = [
            "body" => $goodsName,
            'out_trade_no' => $this->order['trade_no'],
            'total_amount' => $this->order['total_price'],
            'subject' => $this->order['store_name'] . '购买支付',
        ];
        $result = $this->aliApp->wap->Pay($data);
        return $this->rQ($result);
    }

    /**
     * 发起付款码反扫支付
     * @return mixed
     */
    public function aliScanQr()
    {
        $data = [
            "out_trade_no" => $this->order['trade_no'],
            "total_amount" => round($this->order['total_price'], 2),
            "subject" => $this->order['store_name'] . "反扫支付",
            "scene" => "bar_code",
            "auth_code" => $this->message['data']['authCode'],
        ];
        actionLog($data, '请求支付宝反扫支付参数');
        if ($data['total_amount'] == 0) return $this->rFail('支付金额不能等于0');
        $result = $this->aliApp->trade->pay($data);
//        $result = '{"code":"10000","msg":"Success","buyer_logon_id":"136******90","buyer_pay_amount":"0.14","buyer_user_id":"2088012243580922","fund_bill_list":[{"amount":"0.14","fund_channel":"PCREDIT"}],"gmt_payment":"2023-08-03 17:04:27","invoice_amount":"0.14","out_trade_no":"20230803170425","point_amount":"0.00","receipt_amount":"0.14","total_amount":"0.14","trade_no":"2023080322001480921416306571"}';
//        $result = json2arr($result);
        actionLog($result, '请求支付宝反扫支付结果');
        $return = $this->r(99, "请求支付异常", $result);
        if (isset($result['code'])) {
            if (isset($result['buyer_user_id']) && $result['buyer_user_id']) {
                $user = $this->getUserFind(['openid' => $result['buyer_user_id']]);
                if ($user) {
                    $this->order['user_id'] = $user['user_id'];
                    $this->order['user_name'] = $user['name'];
                } else {
                    $insert['openid'] = $result['buyer_user_id'];
                    $insert['wx_id'] = $this->order['sp_id'];
                    $this->order['user_id'] = $this->addUser($insert);
                }
            }

            if ($result['code'] == 10000) {
                $this->startTrans();
                // 结算分润收益
                $flag[] = $this->settlementRevenue();
                $flag[] = $this->paymentSuccessful();
                actionLog($flag,'操作结果');
                $result = flag_check($flag);
                $return = $this->checkTrans($result);
            } else if ($result['code'] == 10003) { // 队列轮询
                $return = $this->r(201, '等待您的支付，超时时间30秒');
                $redis = new \Redis();
                $redis->connect("127.0.0.1");
                $redis->lPush("aliMicroPay", $this->order['order_id']);
                $redis->expire("aliMicroPay", 30);
                $redis->close();
            } else if ($result['code'] == 40004) {
                $this->paymentFailed();
                $return = $this->r(100, '支付失败，请重新支付');
            } else if ($result['code'] == 20000) {
                $this->updateSaleOrders($this->order);
                $return = $this->r(100, '支付异常，请重新支付');
            }
        }
        return $return;
    }

    /**
     * 支付宝订单退款
     * @return mixed
     */
    public function aliRefund()
    {
        $config = $this->strategyPayee;
        $config['isObject'] = false;
        $data = [
            "out_trade_no" => $this->order['trade_no'],
            "trade_no" => $this->order['mch_no'],
            "refund_amount" => round($this->refundData['refund_amount'], 2),
            "out_request_no" => $this->refundData['refund_trade_no'],
            "refund_reason" => "商品退款",
        ];
        $app = Factory::trade($config);
        $result = $app->trade->refund($data);
        if ($result["code"] == "10000") {
            return $this->r(200, "退款成功");
        }
        return $this->r(100, "退款失败，支付宝返回信息：" . $result['msg']);
    }
}