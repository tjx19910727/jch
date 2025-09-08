<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/7/14
 * Time: 15:16
 */

namespace app\pay\controller\notify;


use app\AppFactory\AppFactory;

class CoGoLink
{

    public function notify()
    {
        $postData = input();
//        $postData = '{"requestId":null,"requestTime":null,"responseTime":"2025-07-17T07:08:48.397+00:00","respCode":200,"respMsg":"success","busCode":null,"busMsg":null,"busSubCode":null,"busSubMsg":null,"data":{"transaction":{"merchantNo":"4276000066","terminalSerialNo":"SI00005568","merchantSerialNo":"refund_2025071715084517145942","amount":1,"transactionNo":"TKB353C29A6F00000808386818686976","originalTransactionNo":"TK25969518B800000844094061150208","orderNo":"10220250715000011070","refundAmount":0,"tipAmount":0,"currency":"HKD","transactionType":"08","paymentWay":"01","status":"00","responseCode":"200","transactionTime":"2025-07-17T07:08:47.000+00:00","transactionZoneId":"Europe/Berlin","timeZoneFlag":1,"completionTime":"2025-07-17T07:08:48.000+00:00","cardNoDigest":"195bfc5c9143edf26845368eb9c23d4a7b35de7932c6f829003bcb41403992ec","cardBrand":"01","externalAdditionalData":"{\"remark\":\"test_refund\",\"order_id\":111}"}}}';
        $config['data'] = $postData;
        $config['header'] = request()->header();
        actionLog($config,'回调数据');
        $result = AppFactory::pay($config)->CoGoLink->handleNotify();
    }
}