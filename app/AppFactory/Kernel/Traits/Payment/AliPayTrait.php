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
use app\AppFactory\Kernel\Support\Validate\Pay\VAliPay;

trait AliPayTrait
{
    /**
     * @var Application
     */
    protected $aliApp;

    protected $aliPaymentMethod = [
//        "2" => "aliMobilePay",
        "1" => "aliUrlLink",
        "2" => "aliScanQr",
        "23" => "aliUrlLink",
    ];

    /**
     * 支付宝支付入口
     * @return array|string
     */
    public function aliPay()
    {

        actionLog([
            'order_id' => $this->order['order_id'] ?? null,
            'trade_no' => $this->order['trade_no'] ?? null,
            'pay_method' => $this->order['pay_method'] ?? null,
            'sp_id' => $this->strategyPayee['sp_id'] ?? null,
        ], '支付宝入口-开始分派');

        if (!in_array($this->order['pay_method'],array_keys($this->aliPaymentMethod)))
            return $this->rFail($this->lang("pay_type_not_in_scope"));
        try {
            validate(VAliPay::class)->scene('ali')->check($this->strategyPayee);
        } catch (\Exception $e) {
            actionLog([
                'order_id' => $this->order['order_id'] ?? null,
                'pay_method' => $this->order['pay_method'] ?? null,
                'error' => $e->getMessage(),
            ], '支付宝入口-配置校验失败');
            return $this->rValidate($this->lang($e->getMessage()));
        }

        $publicRoot = rtrim((string) root_path(), "\\/") . DIRECTORY_SEPARATOR . 'public';
        $toPublicAbsPath = static function ($path) use ($publicRoot) {
            $raw = (string) $path;
            if ($raw === '') {
                return $raw;
            }
            // 已是绝对路径则直接使用
            if (preg_match('/^[a-zA-Z]:[\\\\\/]|^\//', $raw)) {
                return $raw;
            }
            return $publicRoot . DIRECTORY_SEPARATOR . ltrim($raw, "\\/");
        };

        // 统一修正证书/私钥路径，避免拼接成 .../publiccert/... 导致请求前即失败
        $this->strategyPayee['private_key_path'] = $toPublicAbsPath($this->strategyPayee['private_key_path'] ?? '');
        $this->strategyPayee['ali_public_key_path'] = $toPublicAbsPath($this->strategyPayee['ali_public_key_path'] ?? '');
        $this->strategyPayee['ali_root_cert_path'] = $toPublicAbsPath($this->strategyPayee['ali_root_cert_path'] ?? '');
        $this->strategyPayee['app_public_key_path'] = $toPublicAbsPath($this->strategyPayee['app_public_key_path'] ?? '');
        $this->strategyPayee['isObject'] = false;
        $url = $this->getUrl('/pay/notify.ali/paymentNotify');
        $this->strategyPayee['notifyUrl'] = $url;

        $this->aliApp = Factory::trade($this->strategyPayee);
        $this->order['sp_id'] = $this->strategyPayee['sp_id'];
        $func_name = $this->aliPaymentMethod[$this->order['pay_method']];
        actionLog([
            'order_id' => $this->order['order_id'] ?? null,
            'pay_method' => $this->order['pay_method'] ?? null,
            'dispatch_method' => $func_name,
        ], '支付宝入口-分派子方法');
        return $this->$func_name();        

    }

    /**
     * 手机发起支付宝支付，返回Form表单格式
     * @return mixed
     */
    public function aliMobilePay()
    {
        $details = $this->getSaleOrdersDetailsColumn(['order_id' => $this->order['order_id']], 'g_name');
        $goodsName = implode(",", $details);
        $data = [
            "body" => $goodsName,
            'out_trade_no' => $this->order['trade_no'],
            'total_amount' => $this->order['total_price'],
            'subject' => $this->order['machine_id'] . '购买支付',
        ];
        $result = $this->aliApp->wap->Pay($data);
        return $this->rAction($result);
    }

    /**
     * 发起付款码反扫支付
     * @return mixed
     * @throws \Exception
     */
    public function aliScanQr()
    {
        $data = [
            "out_trade_no" => $this->order['trade_no'],
            "total_amount" => round($this->order['total_price'], 2),
            "subject" => $this->order['machine_id'] . "反扫支付",
            "scene" => "bar_code",
            "auth_code" => $this->order['pay_code'],
        ];
        actionLog($data, '请求支付宝反扫支付参数');
        if ($data['total_amount'] == 0) return $this->rFail('支付金额不能等于0');
        try {
            $result = $this->aliApp->trade->pay($data);
        } catch (\Throwable $e) {
            actionException($e, 1);
            actionLog([
                'order_id' => $this->order['order_id'] ?? null,
                'trade_no' => $this->order['trade_no'] ?? null,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ], '请求支付宝反扫支付异常');
            return $this->r(100, '请求支付异常：' . $e->getMessage());
        }

        actionLog($result, '请求支付宝反扫支付结果');
        if (!is_array($result) || empty($result)) {
            return $this->r(100, '请求支付异常：支付宝接口无有效响应', is_array($result) ? $result : []);
        }

        $code = (string) ($result['code'] ?? '');
        $msg = (string) ($result['msg'] ?? '');
        $subMsg = (string) ($result['sub_msg'] ?? '');
        $return = $this->r(99, '请求支付异常：' . trim($msg . ' ' . $subMsg), $result);

        if ($code !== '') {
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

            if ($code == '10000') {
                return $this->r(200,$this->lang("init_payment_success"));
//                $this->startTrans();
//                try {// 结算分润收益
//                    $flag[] = $this->settlementRevenue();
//                    $flag[] = $this->paymentSuccessful();
//                    actionLog($flag, '操作结果');
//                    $checkFlag = flag_check($flag);
//                    $return = $this->checkTrans($checkFlag);
//                } catch (\Exception $e) {
//                    $this->rollbackTrans();
//                    actionException($e,1);
//                    $return = $this->rTryCatch($e->getMessage());
//                }
            } else if ($code == '10003') { // 队列轮询
                $return = $this->r(201, '等待您的支付，超时时间30秒');
                $redisExpire = (env("Payment.microPayOverTime") ?? 0) + 60;
                $redis = new \Redis();
                $config = config("redis");
                $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
                if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
                $redis->lPush("microPay", json_encode(['order_id' => $this->order['order_id'],'time' => time(),'pay_type' => "ali"], 256 + 64));
                $redis->expire("microPay", $redisExpire);
                $redis->close();
            } else if ($code == '40004') {
                $this->paymentFailed();
                $return = $this->r(100, '支付失败，请重新支付');
            } else if ($code == '20000') {
                $this->updateSaleOrders($this->order);
                $return = $this->r(100, '支付异常，请重新支付');
            } else {
                $return = $this->r(100, '请求支付异常：' . trim($msg . ' ' . $subMsg), $result);
            }
        } else {
            $return = $this->r(100, '请求支付异常：支付宝返回缺少 code', $result);
        }
        return $return;
    }

    /**
     * 支付宝创建预生成订单支付二维码
     * @return mixed
     */
    public function aliUrlLink()
    {
        try {
            $data = [
                'out_trade_no' => $this->order['trade_no'],
                'total_amount' => round($this->order['total_price'],2),
                'subject' => $this->order['machine_id'] . '购买支付',
            ];
            actionLog([
                'order_id' => $this->order['order_id'] ?? null,
                'trade_no' => $data['out_trade_no'],
                'total_amount' => $data['total_amount'],
                'subject' => $data['subject'],
                'private_key_path' => $this->strategyPayee['private_key_path'] ?? null,
                'ali_public_key_path' => $this->strategyPayee['ali_public_key_path'] ?? null,
                'ali_root_cert_path' => $this->strategyPayee['ali_root_cert_path'] ?? null,
                'app_public_key_path' => $this->strategyPayee['app_public_key_path'] ?? null,
            ], '支付宝预下单-请求参数');
            $result = $this->aliApp->trade->preCreate($data);
            actionLog([
                'order_id' => $this->order['order_id'] ?? null,
                'code' => $result['code'] ?? null,
                'msg' => $result['msg'] ?? null,
                'sub_msg' => $result['sub_msg'] ?? null,
                'has_qr_code' => !empty($result['qr_code']),
            ], '支付宝预下单-返回结果');
            if ($result) {
                if ($result['code'] == 10000) {
                    $this->returnData['order'] = $this->order;
                    $this->returnData['paymentUrlLink'] = $result['qr_code'];
                    $this->returnData['qrCodeLink'] = $result['qr_code'];
                    $this->returnData['result'] = $result;
                    return $this->r(200, $result['msg'], $this->returnData);
                } else {
                    $msg = $result['msg'] . "；";
                    if (isset($result['sub_msg'])) $msg .= $result['sub_msg'] . "；";
                    return $this->r(100, $this->lang("init_payment_fail") . '：' . $msg, $result);
                }
            }
            actionLog([
                'order_id' => $this->order['order_id'] ?? null,
            ], '支付宝预下单-空响应');
            return $this->r(100, $this->lang("init_payment_fail") . '：支付宝预下单无响应', [
                'order_id' => $this->order['order_id'] ?? null,
                'trade_no' => $data['out_trade_no'] ?? null,
            ]);
        } catch (\Throwable $e) {
            actionException($e,1);
            actionLog([
                'order_id' => $this->order['order_id'] ?? null,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ], '支付宝预下单-异常');
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 支付宝订单退款
     * @return mixed
     * @throws \Exception
     */
    public function aliRefund()
    {
        $config = $this->strategyPayee;
        $config['isObject'] = false;
        $data = [
            "out_trade_no" => $this->order['trade_no'],
            "refund_amount" => round($this->refundData['refund_amount'], 2),
            "out_request_no" => $this->refundData['refund_trade_no'],
            "refund_reason" => "商品退款",
        ];
        if ($this->order['mch_no']) $data["trade_no"] = $this->order['mch_no'];
        $app = Factory::trade($config);
        $result = $app->trade->refund($data);
        if ($result["code"] == "10000") {
            return $this->r(200, "退款成功");
        }
        return $this->r(100, "退款失败，支付宝返回信息：" . $result['msg'] . $result['sub_msg']);
    }

    /**
     * 支付宝撤销订单
     * @return mixed
     * @throws \Exception
     */
    public function aliCancel()
    {
        $config = $this->strategyPayee;
        $config['isObject'] = false;
        $app = Factory::trade($config);
        $result = $app->trade->cancel($this->order['trade_no']);
        actionLog($result,'关闭订单');
        if ($result['code'] == "10000") {
            return $this->r(200,$this->lang("cancel_payment_success"));
        }
        return $this->r(100,$this->lang("cancel_payment_fail") . ($result['msg'] ?? "") . ($result['sub_msg'] ?? ""));
    }
}