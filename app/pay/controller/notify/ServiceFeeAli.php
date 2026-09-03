<?php

namespace app\pay\controller\notify;

use app\AppFactory\AppFactory;

class ServiceFeeAli
{
    public function paymentNotify()
    {
        $message = json2arr(input());
        actionLog($message, '设备服务费支付宝支付回调');
        $success = AppFactory::pay()->serviceFeeAliNotify->handlePayment($message);
        echo $success ? 'success' : 'failure';
    }
}
