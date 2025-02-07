<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/2
 * Time: 9:41
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Pay\PayBaseClient;

class AliClient extends PayBaseClient
{
    use UserTrait;
    use AfterOrderPaymentTrait;
    use SaleOrdersRevenueTrait;
    use MachineMqRecordTrait;

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
            actionLog($this->getLS(),'【SQL】查询订单');
            actionLog($this->order,'订单数据');
            if ($this->order && $this->order['pay_status'] == 1) {
                $this->order = $this->order->toArray();
                if ($this->order['pay_status'] < 3) {
                    if (isset($message['buyer_id'])) {
                        $user = $this->getUserFind(['openid' => $message['buyer_id']], 'user_id,name');
                        if ($user) {
                            $this->order['user_id'] = $user['user_id'];
                            $this->order['user_name'] = $user['name'];
                        } else {
                            $insert['openid'] = $message['buyer_id'];
                            $insert['type'] = 3;
                            $this->order['user_id'] = $this->addUser($insert);
                            actionLog($this->getLS(),'增加支付宝会员信息');
                            $this->order['user_name'] = $message['buyer_logon_id'];
                        }
                    }
                    $this->order['mch_no'] = $message['trade_no'];
                    $this->startTrans();
                    // 结算分润收益
                    try {
                        $flag[] = $this->settlementRevenue();
                        $flag[] = $this->paymentSuccessful();
                        actionLog($flag,'flag');
                        $result = flag_check($flag);
//                    $this->rollbackTrans();
                        $result = $this->checkTrans($result);
                        actionLog($result, '支付成功处理事务结果');
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        actionException($e, 1);
                    }
                }
            } else {
                actionLog($this->getLS(), "没有查到订单");
            }
        } else {
            $result = $this->paymentFailed();
            actionLog($result, '支付失败结果');
        }
        return "ok";
    }
}