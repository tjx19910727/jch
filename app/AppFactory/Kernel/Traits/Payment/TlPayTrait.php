<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/1
 * Time: 16:05
 */

namespace app\AppFactory\Kernel\Traits\Payment;



trait TlPayTrait
{
    /**
     * @var Application
     */
    protected $tlApp;
    protected $tlConfig;
    protected $payType = "W01";

    protected $tlPaymentMethod = [
        "31" => "unitOrderPay",
        "32" => "scanQrPay",
    ];

    /**
     * 通联支付
     * @return mixed
     */
    public function tlPay()
    {
//        $this->payType = $this->message['data']["payType"] ?? "W01";
        if (!in_array($this->order['payment_method'],array_keys($this->tlPaymentMethod)))
            return $this->rFail("支付方式不在允许范围内");
        $this->order['sp_id'] = $this->strategyPayee['sp_id'];
        $this->tlApp = TlFactory::apiWeb($this->strategyPayee);
        $func_name = $this->tlPaymentMethod[$this->order['payment_method']];
        return $this->$func_name();
    }

    /**
     * 通联支付——支付链接
     * @param $config
     * @param string $payType
     * @return array|string
     * @throws \tonglian\Kernel\Exceptions\Exception
     */
    public function unitOrderPay()
    {
        $params = [
            'cusid' => $this->strategyPayee['mch_id'],
            'appid' => $this->strategyPayee['app_id'],
            'version' => '11',
            'trxamt' => bcmul($this->order['total_price'],100),
            'reqsn' => $this->order['trade_no'],
            'paytype' => $this->payType,
            'randomstr' => substr($this->order['trade_no'],14,6),
            'validtime' => '5',
            'notify_url' => $this->getUrl('/http/pay.tl/paymentNotify'),
            'signtype' => "RSA",
            'remark' => $this->order['store_name'] . "【" . $this->order['terminal_no'] . "】",
            'acct' => $this->getUserValue(['user_id' => $this->order['user_id']],'openid') ?? "",
        ];
        actionLog($params,'请求通联扫码支付参数');
        $result = $this->tlApp->unitOrder->pay($params);
        $result = json2arr($result);
        actionLog($result,'请求通联扫码支付结果');
        if ($result['retcode'] == "SUCCESS") {
            if ($result['trxstatus'] == "0000") {
                $this->order['payment_code'] = $result['payinfo'];
                $result['payinfo'] = json2arr($result['payinfo']);
                $return = $this->r(200,'请求支付成功',['paymentUrlLink' => $result['payinfo'],"result" => $result,"order" => $this->order]);
                actionLog($return,'返回前端数据');
                return $return;
            }
            return $this->rFail('请求支付失败：'. $result['errmsg']);
        }
        return $this->rFail('请求支付失败：'. $result['retmsg']);
    }

    /**
     * 反扫支付授权码
     * @param $config
     * @param $authCode
     * @return array|string
     * @throws \tonglian\Kernel\Exceptions\Exception
     */
    public function scanQrPay()
    {
        $terminfo = [
            'termno' => $this->order['terminal_no'],
            'devicetype' => '01',
        ];
        $params = [
            'appid' => $this->strategyPayee['app_id'],
            'cusid' => $this->strategyPayee['mch_id'],
            'randomstr' => substr($this->order['trade_no'],14,6),
            'trxamt' => bcmul($this->order['total_price'],100),
            'reqsn' => $this->order['trade_no'],
            'authcode' => $this->order['payment_code'],
            'notify_url' => $this->getUrl('/http/pay.tl/paymentNotify'),
            'signtype' => "RSA",
            'terminfo' => json_encode($terminfo),
            'remark' => $this->order['store_name'] . "【" . $this->order['terminal_no'] . "】",
        ];
        actionLog($params,'请求通联反扫支付参数');
        $result = $this->tlApp->unitOrder->scanQrPay($params);
        $result = json2arr($result);
        actionLog($result,'请求通联反扫支付结果');
        if (isset($result['acct']) && $result['acct'] && $result['acct'] != "000000") {
            $user = $this->getUserFind(['openid' => $result['acct']]);
            if ($user) {
                $this->order['user_id'] = $user['user_id'];
                $this->order['user_name'] = $user['name'];
            } else {
                $insert['openid'] = $result['acct'];
                $insert['type'] = 2;
                if (substr($result['acct'],0,4) == 2088) $insert['type'] = 3;
                $this->order['user_id'] = $this->addUser($insert);
            }
        }

        if ($result['retcode'] == "SUCCESS") {
            if ($result['trxstatus'] == "0000") {
                $this->startTrans();
                try {// 结算分润收益
                    $flag[] = $this->settlementRevenue();
                    $flag[] = $this->paymentSuccessful();
                    $result = flag_check($flag);
                    actionLog($flag, '支付成功flag');
                    $return = $this->checkTrans($result);
                    return $return;
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e,1);
                    return $this->rTryCatch($e->getMessage());
                }
            }
            if ($result['trxstatus'] == '2008' || $result['trxstatus'] == '2000') {
                $this->updateSaleOrders($this->order);
                return $this->r(201,'发起支付成功，等待用户支付');
            }
            $this->paymentFailed();
            return $this->rFail('支付失败：'. $result['errmsg']);
        }
        return $this->rFail('支付失败：'.$result['retmsg']);
    }

    /**
     * 通联退款
     * @return mixed
     */
    public function tlRefund()
    {
        try {
            $params = [
                'appid' => $this->strategyPayee['app_id'],
                'cusid' => $this->strategyPayee['mch_id'],
                'version' => '11',
                'oldreqsn' => $this->order['trade_no'],
                'randomstr' => $this->get_rand_string(6),
                'reqsn' => $this->refundData['refund_trade_no'],
                'trxamt' => bcmul($this->refundData['refund_amount'],100),
                'signtype' => "RSA",
            ];
            actionLog($params,'退款请求参数');
            $this->tlApp = TlFactory::apiWeb($this->strategyPayee);
            $result = $this->tlApp->tranx->refund($params);
            actionLog($result,'退款申请返回结果');
            if ($result['retcode'] == "SUCCESS") {
                if ($result['trxstatus'] == "0000") {
                    return $this->r(200, '发起退款成功',$result);
                }
                return $this->r(100,'退款失败：' . $result['errmsg']);
            }
            return $this->r(100,'退款失败：' . $result['retmsg']);
        } catch (Exception $e) {
            return $this->rValidate($e->getMessage());
        }
    }



}