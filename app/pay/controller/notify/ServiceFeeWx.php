<?php

namespace app\pay\controller\notify;

use app\AppFactory\AppFactory;

class ServiceFeeWx
{
    public function paymentNotify()
    {
        try {
            $xml = file_get_contents('php://input');
            actionLog($xml, '设备服务费微信支付回调XML');
            $message = FromXml($xml);
            AppFactory::pay()->serviceFeeWxNotify->handlePayment($message);
        } catch (\Throwable $e) {
            actionException($e, 1);
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ']]></return_msg></xml>';
        }
    }

    public function refundNotify()
    {
        try {
            AppFactory::pay()->serviceFeeWxNotify->handleRefund(json2arr(input()));
        } catch (\Throwable $e) {
            actionException($e, 1);
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ']]></return_msg></xml>';
        }
    }
}
