<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/2
 * Time: 9:41
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Pay\PayBaseClient;

class AliClient extends PayBaseClient
{
    use UserTrait;
    use AfterOrderPaymentTrait;

    protected $order;

    /**
     * 处理支付宝支付回调
     * @param $message
     * @return string
     */
    public function handle($message)
    {
        $outTradeNo = $message['out_trade_no'];

        if ($message['trade_status'] === 'TRADE_SUCCESS' || $message['trade_status'] === 'TRADE_FINISHED') {
            $this->order = $this->getSaleOrdersFind(['trade_no' => $outTradeNo]);
            if ($this->order) {
                if (isset($message['buyer_id'])) {
                    $user = $this->getUserFind(['openid' => $message['buyer_id']],'user_id,name');
                    $this->order['user_id'] = $user['user_id'];
                    $this->order['user_name'] = $user['name'];
                }
                $this->startTrans();
                // 结算分润收益
                try {
                    $flag[] = $this->settlementRevenue();
                    $flag[] = $this->paymentSuccessful();
                    $result = flag_check($flag);
                    $result = $this->checkTrans($result);
                    actionLog($result, '支付成功处理事务结果');
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e,1);
                }
            } else {
                actionLog($this->getLS(), "没有查到订单");
            }
        } else {
            $result = $this->paymentFailed();
            actionLog($result,'支付失败结果');
        }
        return "ok";
    }
}