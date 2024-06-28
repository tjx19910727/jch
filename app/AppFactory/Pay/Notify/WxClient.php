<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/2
 * Time: 8:57
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Pay\PayBaseClient;

class WxClient extends PayBaseClient
{
    use StrategyPayeeTrait;
    use AfterOrderPaymentTrait;
    use WxPayTrait;

    protected $wxConfig;
    protected $order;
    public $refund_no;
    public $strategyPayee;

    public function handle($message)
    {
        $this->wxConfig = $this->getStrategyPayeeContent(['sp_id' => $message['sp_id']]);
        if (!$this->wxConfig) {
            actionLog($this->getLS(),'查无微信支付配置信息');
            echo "success";
            die();
        }
//        $app = Factory::payment($this->wxConfig);
//        $response = $app->handlePaidNotify(function ($message, $fail) {
            actionLog($message, "message");
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                $mch_no = $message['transaction_id'];
                $outTradeNo = $message['out_trade_no'];
                $this->order = $this->getSaleOrdersFind(['trade_no' => $outTradeNo]);
                if (!$this->order) {
                    actionLog($this->getLS(),'查无订单信息');
                    return true;
                }
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
                    // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                    $this->order['payment_type'] = 1;
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
                        actionException($e,1);
                    }
                } elseif ($message['result_code'] === 'FAIL') {
                    $this->paymentFailed();
                }
            } else {
                return ('通信失败，请稍后再通知我');
            }
            return true; // 返回处理完成
//        });
//        $response->send();
    }

    public function handleRefund()
    {
        try {
            $this->refundTradeNo = $this->data['refundRequestNum'];
            if ($this->data['event_type'] == 'REFUND.SUCCESS') {
                $this->strategyPayee = $this->getStrategyPayeeContent(['sp_id' => $this->data['sp_id']]);
                if (!is_array($this->strategyPayee)) return $this->strategyPayee;

                $this->initWpApp();

                $this->wpApp->

                $this->refund_no = $this->data['orderNum'];


                $this->startTrans();
                try {
                    $result = $this->refundSuccess();
                    if ($result === true) {
                        $this->commitTrans();
                    } else {
                        $this->rollbackTrans();
                    }
                    actionLog($result, '处理退款成功数据结果');
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e, 1);
                }
                return 200;
            }
            if ($this->data['refundStatus'] == 'FAIL') {
                $this->refundFail();
                return 200;
            }
            return 200;
        } catch (\Exception $e) {
            actionException($e,1);
            return 200;
        }
    }
}