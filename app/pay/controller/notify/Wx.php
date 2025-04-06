<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/2
 * Time: 8:44
 */

namespace app\pay\controller\notify;


use app\AppFactory\AppFactory;

class Wx
{
    public function pay_notify()
    {
        $xml = file_get_contents("php://input");
        actionLog($xml, "xml");
        $msg = FromXml($xml);
        actionLog($msg, "message");
        try {
//            actionLog($postData, '回调通知数据');
            if (isset($msg['attach'])) {
                $temp = explode("|", $msg['attach']);
                $msg['order_id'] = $temp[0];
                $msg['sp_id'] = $temp[1];
            }
            AppFactory::pay()->wxNotify->handle($msg);
            echo "success";
        } catch (\Exception $e) {
            actionException($e, 1);
            echo "success";
        }
    }

    public function refundOrderNotify()
    {
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData, '回调数据');
        $xml = file_get_contents("php://input");
        actionLog($xml, "xml");
        $msg = FromXml($xml);
        actionLog($msg, "message");
//        $config['key'] = env("api.md5Key");
        $config['data'] = $postData;
        $result = AppFactory::pay($config)->wxNotify->handleRefund();
    }
}