<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/2
 * Time: 8:57
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Pay\PayBaseClient;
use EasyWeChat\Factory;

class WxClient extends PayBaseClient
{
    use StrategyPayeeTrait, StrategyMachineTrait;
    use SaleOrdersRefundTrait, SaleOrdersRevenueTrait;
    use AfterOrderPaymentTrait, AfterOrderRefundTrait;
    use WxPayTrait;
    use MachineMqRecordTrait;

    protected $wxConfig;
    public $refund_no;
    public $strategyPayee;

    /**
     * @param $message
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function handle($message)
    {
        $this->wxConfig = $this->getStrategyPayeeContent(['sp_id' => $message['sp_id'], 'sm.s_type' => 1]);
        if (!$this->wxConfig) {
            actionLog($this->getLS(), '查无微信支付配置信息');
            echo "success";
            die();
        }
        $app = Factory::payment($this->wxConfig);
        $response = $app->handlePaidNotify(function ($message, $fail) {
            actionLog($message, "message");
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                $mch_no = $message['transaction_id'];
                $outTradeNo = $message['out_trade_no'];
                $this->order = $this->getSaleOrdersFind(['trade_no' => $outTradeNo]);
                if (!$this->order) {
                    actionLog($this->getLS(), '查无订单信息');
                    return true;
                }
                $this->order = $this->order->toArray();
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
                    if ($this->order['pay_status'] != 3) {
                        // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                        $this->order['pay_type'] = 1;
                        $this->order['mch_no'] = $mch_no;

                        $this->startTrans();
                        try {// 结算分润收益
                            $flag[] = $this->settlementRevenue();
                            $flag[] = $this->paymentSuccessful();
                            $result = flag_check($flag);
                            $return = $this->checkTrans($result);
                            actionLog($return, '处理支付成功事务');
                        } catch (\Exception $e) {
                            $this->rollbackTrans();
                            actionException($e, 1);
                        }
                    }
                } elseif ($message['result_code'] === 'FAIL') {
                    $this->paymentFailed();
                }
            } else {
                return ('通信失败，请稍后再通知我');
            }
            return true; // 返回处理完成
        });
        $response->send();
    }

    public function handleRefund()
    {
        try {
            $this->strategyPayee = $this->getStrategyPayeeContent(['sp_id' => $this->data['sp_id'], 'sm.s_type' => 1]);
            if (!$this->strategyPayee) {
                actionLog($this->getLS(), '查无微信支付配置信息');
            }
            $this->initWpApp();
            $response = $this->wpApp->handleRefundedNotify(function ($result, $msg, $alert) {
                if ($result['return_code'] === 'SUCCESS') {
                    $this->refundTradeNo = $msg['out_refund_no'];
                    // 用户是否支付退款成功
                    if ($msg['refund_status'] == "SUCCESS") {
                        try {
                            $this->startTrans();
                            $this->data['refundAmount'] = bcdiv($msg['refund_fee'], 100, 2);
                            $result = $this->refundSuccess();
                            if ($result === true) {
                                $this->commitTrans();
                            } else {
                                $this->rollbackTrans();
                            }
                        } catch (\Exception $e) {
                            $this->rollbackTrans();
                            actionException($e, 1);
                        }
                    }
                    if (in_array($msg['refund_status'],["CHANGE","REFUNDCLOSE","FAIL"])) {
                        $this->refundFail();
                    }
                }
                return true;
            });
            $response->send();
        } catch (\Exception $e) {
            actionException($e, 1);
            return 200;
        }
    }
}