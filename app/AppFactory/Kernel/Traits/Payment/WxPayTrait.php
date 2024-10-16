<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:05
 */

namespace app\AppFactory\Kernel\Traits\Payment;
use EasyWeChat\Factory;
use EasyWeChat\Payment\Application;


/**
 * 微信支付处理文件
 * Trait WxPayTrait
 * @package app\AppFactory\Kernel\Traits\Pay
 */
trait WxPayTrait
{

    /**
     * @var Application
     */
    public $wpApp;

    protected $wxPaymentMethod = [
        "1" => "wxNative",
        "2" => "wxScanQr",
//        "12" => "wxJsApi",
//        "13" => "wxMini",
//        "15" => "wxFacePay",
    ];

    public function initWpApp()
    {
        $this->strategyPayee['mchid'] = $this->strategyPayee['mch_id'];
        $this->strategyPayee['key_path'] = root_path() . "public" . $this->strategyPayee['key_path'];
        $this->strategyPayee['cert_path'] = root_path() . "public" . $this->strategyPayee['cert_path'];
        $this->strategyPayee['privateKey'] = $this->strategyPayee['key_path'];
        $this->wpApp = Factory::payment($this->strategyPayee);
    }

    // 微信支付入口
    // 查询订单门店绑定的支付配置
    // 根据订单的要求调用不同的支付方式，获取二维码，付款码支付
    public function wxPay()
    {
        if (!in_array($this->order['pay_method'],array_keys($this->wxPaymentMethod)))
            return $this->rFail("支付方式不在允许范围内");
        $this->initWpApp();
        $this->order['sp_id'] = $this->strategyPayee['sp_id'];
//        if ($this->order['pay_method'] != "14") {
//            return $this->rFail("当前模式下微信仅支持Native支付");
//        }
        $func_name = $this->wxPaymentMethod[$this->order['pay_method']];
        return $this->$func_name();
    }

    /**
     * 11. 微信付款码支付
     * @return int
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wxScanQr()
    {
        $params = [
            "body" => "支付订单",
            "out_trade_no" => $this->order['trade_no'],
            "total_fee" => round($this->order['total_price'] * 100, 0),
            "auth_code" => $this->order['pay_code'],
        ];
        actionLog($params,'付款码支付参数');
        $result = $this->wpApp->pay($params);
        actionLog($result,'付款码支付');
        $return = $this->r(100,"不明状态码",$result);

//        if (isset($result['openid']) && $result['openid']) {
//            $user = $this->getUserFind(['openid' => $result['openid']]);
//            if ($user) {
//                $this->order['user_id'] = $user['user_id'];
//                $this->order['user_name'] = $user['name'];
//            } else {
//                $insert['openid'] = $result['openid'];
//                $insert['wx_id'] = $this->getOpenPlatformWxValue(['authorizer_appid' => $result['appid']],'wx_id');
//                $this->order['user_id'] = $this->addUser($insert);
//            }
//        }

        if ($result['return_code'] == "SUCCESS") {
            if ($result['result_code'] == "SUCCESS" && $result['trade_type'] == "MICROPAY") {
                // 成功
                $this->startTrans();
                // 结算分润收益
                $flag[] = $this->settlementRevenue();
                $flag[] = $this->paymentSuccessful();
                $result = flag_check($flag);
                $return = $this->checkTrans($result);
            }
            if ($result['err_code'] == "USERPAYING") {
                $redisExpire = (env("Payment.microPayOverTime") ?? 0) + 60;
                // 支付中
                $redis = new \Redis();
                $config = config("redis");
                $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
                if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
                $redis->lPush("microPay", json_encode(['order_id' => $this->order['order_id'],'time' => time(),'pay_type' => "wx"], 256 + 64));
                $redis->expire("microPay",$redisExpire);
                $redis->close();
                $return = $this->r(201,'等待用户支付');
            }
        }
        if ($result['result_code'] == "FAIL") {
            // 支付失败
            $this->paymentFailed();
            $return = $this->rFail("支付失败");
        }
        return $return;
    }

    /**
     * 12. 微信JSAPI支付
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wxJsApi()
    {
//        if (!$this->order['user_id']) return $this->rFail("订单未绑定会员ID");
//        $openid = $this->getUserValue(['user_id' => $this->order['user_id']],'openid');
//        if (!$openid) return $this->rFail("查无会员OPENID");
//        $param = [
//            'body' => '商品购买',
//            'out_trade_no' => $this->order['trade_no'],
//            'total_fee' => round($this->order['total_price'] * 100), // 单位分
//            'notify_url' => $this->getUrl('/http/pay.wx/pay_notify'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
//            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
//            'openid' => $openid,
//            "attach" => json_encode(['sp_id' => $this->strategyPayee['sp_id'],'order_id' => $this->order['order_id']]),
//        ];
//        actionLog($param,'统一下单请求参数');
//        $result = $this->wpApp->order->unify($param);
//        actionLog($result,'统一下单返回参数');
//        if ($result['return_code'] == 'FAIL') return $this->rFail("发起微信支付：" . $result['return_msg']);
//        if ($result['result_code'] != 'SUCCESS' && isset($result['err_code_des'])) return $this->rFail( '发起微信支付失败：' . $result['err_code_des']);
//
//        $json = $this->wpApp->jssdk->bridgeConfig($result['prepay_id'],false); // 返回 json 字符串，如果想返回数组，传第二个参数 false
//        actionLog($json,'json');
//        return $this->r(200,"发起微信支付成功", ['order' => $this->order, "pay" => $json, 'type' => 1]);
    }

    /**
     * Native支付
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wxNative()
    {
        $param = [
            'body' => "购买支付",
            'out_trade_no' => $this->order['trade_no'],
            'time_expire' => date("YmdHis",(time()+300)),
            'total_fee' => round($this->order['total_price'] * 100),
            'notify_url' => $this->getUrl('/pay/notify.wx/pay_notify'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => "NATIVE",
            "attach" => $this->order['order_id'] . "|" . $this->strategyPayee['sp_id'],
        ];
        actionLog($param,'统一下单请求参数');
        $result = $this->wpApp->order->unify($param);
        actionLog($result,'统一下单返回参数');
        if (isset($result['code_url'])) {
            $this->returnData['order'] = $this->order;
            $this->returnData['paymentUrlLink'] = $result['code_url'];
            $this->returnData['result'] = $result;
            return $this->r(200,'SUCCESS',$this->returnData);
        }
        return $this->r(100, $result['err_code_des']);
    }

    /**
     * 微信退款
     * @return mixed
     */
    public function wxRefund()
    {
        try {
            $notifyUrl = $this->getUrl('/pay/notify.wx/refundOrderNotify/sp_id/' . $this->strategyPayee['sp_id'] . "/order_id/" . $this->order['order_id']);
            $this->initWpApp();

            $totalFee = bcmul($this->order['total_price'], 100);
            $refundFee = bcmul($this->refundData['refund_amount'], 100);
            $result = $this->wpApp->refund->byOutTradeNumber($this->refundData['order_trade_no'],$this->refundData['refund_trade_no'],$totalFee,$refundFee,[
                "refund_desc" => $this->refundData['remark'],
                "notify_url" => $notifyUrl,
            ]);
            actionLog($result, "微信退款返回数据");
            $return = $this->r(100, "无此退款状态", $result);
            switch ($result['result_code']) {
                case "SUCCESS":
                    $return = $this->r(200, "退款申请成功");
                    break;
                case "CLOSED":
                    $this->refundFail();
                    $return = $this->r(401, "退款关闭");
                    break;
                case "PROCESSING":
                    $return = $this->r(402, "退款处理中");
                    break;
                case "ABNORMAL":
                    $this->refundFail();
                    $return = $this->r(403, "退款异常");
                    break;
                case "FAIL":
                    $return = $this->r(404, $result['err_code_des']);
                    break;
            }
            return $return;
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 微信关闭订单
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wxCancel()
    {
        $result = $this->wpApp->order->close($this->order['trade_no']);
        actionLog($result, '微信支付关闭订单结果');
        if (isset($result['return_code']) && $result['return_code'] == 'success') {
            return $this->r(200, $this->lang("cancel_payment_success"));
        }
        $msg = '';
        if (isset($result['return_msg'])) $msg .= $result['return_msg'] . "；";
        if (isset($result['err_code'])) $msg .= $result['err_code'] . "；";
        if (isset($result['err_code_des'])) $msg .= $result['err_code_des'] . "；";
        return $this->r(100, $this->lang("cancel_payment_fail") . '：' . $msg, $result);
    }

}