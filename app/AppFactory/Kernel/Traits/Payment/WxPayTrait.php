<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:05
 */

namespace app\AppFactory\Kernel\Traits\Payment;
use WeChatPayV3\Factory;
use WeChatPayV3\Payment\Application;


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
    protected $wpApp;

    protected $wxPaymentMethod = [
        "11" => "wxScanQr",
        "12" => "wxJsApi",
        "13" => "wxMini",
        "14" => "wxNative",
        "15" => "wxFacePay",
    ];

    // 微信支付入口
    // 查询订单门店绑定的支付配置
    // 根据订单的要求调用不同的支付方式，获取二维码，付款码支付
    public function wxPay()
    {
        if (!in_array($this->order['pay_method'],array_keys($this->wxPaymentMethod)))
            return $this->rFail("支付方式不在允许范围内");
        $this->wpApp = Factory::payment($this->strategyPayee);
        $this->order['sp_id'] = $this->strategyPayee['sp_id'];
        if ($this->order['payment_method'] != "14") {
            return $this->rFail("当前模式下微信仅支持Native支付");
        }
        $func_name = $this->wxPaymentMethod[$this->order['payment_method']];
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
//        $params = [
//            "body" => "支付订单",
//            "out_trade_no" => $this->order['trade_no'],
//            "total_fee" => round($this->order['total_price'] * 100, 0),
//            "auth_code" => $this->order['payment_code'],
//        ];
//        actionLog($params,'付款码支付参数');
//        $result = $this->wpApp->micropay->pay($params);
//        actionLog($result,'付款码支付');
//        $return = $this->r(100,"不明状态码",$result);
//
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
//
//        if ($result['return_code'] == "SUCCESS") {
//            if ($result['result_code'] == "SUCCESS" && $result['trade_type'] == "MICROPAY") {
//                // 成功
//                $this->startTrans();
//                // 结算分润收益
//                $flag[] = $this->settlementRevenue();
//                $flag[] = $this->paymentSuccessful();
//                $result = flag_check($flag);
//                $return = $this->checkTrans($result);
//            }
//            if ($result['err_code'] == "USERPAYING") {
//                // 支付中
//                $redis = new \Redis();
//                $redis->connect("127.0.0.1", "6379");
//                $redis->lPush("microPay", json_encode(['order_id' => $this->order['order_id'],'time' => time(),'pay_type' => "wx"], 256 + 64));
//                $redis->expire("microPay",120);
//                $redis->close();
//                $return = $this->r(201,'等待用户支付');
//            }
//        }
//        if ($result['result_code'] == "FAIL") {
//            // 支付失败
//            $this->paymentFailed();
//            $return = $this->rFail("支付失败");
//        }
//        return $return;
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
     */
    public function wxNative()
    {
        $param = [
            'appid' => $this->strategyPayee['appid'],
            'mchid' => $this->strategyPayee['mchid'],
            'description' => $this->order['machine_id'] . '商品购买',
            'out_trade_no' => $this->order['trade_no'],
            'time_expire' => date("Y-m-dTH:i:s+08:00"),
            "attach" => json_encode(['sp_id' => $this->strategyPayee['sp_id'],'order_id' => $this->order['order_id']]),
            'notify_url' => $this->getUrl('/pay/notify.wx/pay_notify'), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'amount' => [
                "total" => round($this->order['total_price'] * 100), // 单位分
                "currency" => "CNY", // 人民币
            ]
        ];
        actionLog($param,'统一下单请求参数');
        $result = $this->wpApp->transactions->native($param);
        actionLog($result,'统一下单返回参数');
        if (isset($result['code_url'])) {
            return $this->r(200,'SUCCESS',['code_url' => $result['code_url']]);
        }
        return $this->r(100,'fail：' . $result['message']);
    }

    /**
     * 微信退款
     * @return mixed
     */
    public function wxRefund()
    {
        try {
            $notifyUrl = $this->getUrl('/http/pay.wx/refundOrderNotify');
            $app = Factory::payment($this->strategyPayee);
            $result = $app->refund->byTransactionId($this->order['mch_no'], $this->refundData['refund_trade_no'], bcmul($this->order['total_price'], 100), bcmul($this->refundData['refund_amount'], 100),
                [
                    'refund_desc' => "商品退款",
                    'notify_url' => $notifyUrl,
                ]);
            actionLog($result, "微信退款返回数据");
            $return = $this->r(100, "无此退款状态", $result);
            switch ($result['result_code']) {
                case "SUCCESS":
                    $return = $this->r(200, "退款申请成功");
                    break;
                case "CLOSED":
                    $return = $this->r(401, "退款关闭");
                    break;
                case "PROCESSING":
                    $return = $this->r(402, "退款处理中");
                    break;
                case "ABNORMAL":
                    $return = $this->r(403, "退款异常");
                    break;
                case "FAIL":
                    $return = $this->r(404, $result['err_code_des']);
                    break;
            }
            return $return;
        } catch (InvalidConfigException $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }


    /**
     * 企业付款到零钱
     * @param $data
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function transferBalance($data)
    {
        $app = Factory::payment($this->strategyPayee);
        $params = [
            "partner_trade_no" => $data['trade_no'], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            "openid" => $data['openid'],
            "check_name" => "NO_CHECK",  // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            "re_user_name" => '',   // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            "amount" => $data['amount'],
            "desc" => $data['desc'],
        ];
        $result = $app->transfer->toBalance($params);
        return $result;
    }

    /**
     * 查询企业付款至零钱
     * @param $trade_no
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryTransferBalance($trade_no)
    {
        $app = Factory::payment($this->strategyPayee);
        $result = $app->transfer->queryBalanceOrder($trade_no);
        return $result;
    }

    /**
     * 商户转账至零钱
     * @param $data
     * @return bool|string
     */
    public function transferBatches($data)
    {
        $app = \WeChatPayV3\Factory::payment($this->strategyPayee);
        $details = $data['details'];
        $params = [
            "appid" => $this->strategyPayee['app_id'],
            "out_batch_no" => $data['trade_no'],
            "batch_name" => $data['batch_name'],
            "batch_remark" => $data['desc'],
            "total_amount" => $data['amount'],
            "total_num" => 1,
            "transfer_detail_list" => [$details],
        ];
        $result = $app->transfer->batches($params);
        return $result;
    }
}