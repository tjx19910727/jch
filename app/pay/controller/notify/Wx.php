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
        $postData = input();
        actionLog($postData,'接收数据');
        $xml = file_get_contents("php://input");
        actionLog($xml, "xml");
        $msg = FromXml($xml);
        actionLog($msg, "message");
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '回调通知数据');
            if (!$postData) die("success");
            $config['key'] =  env("api.md5Key");
            $config['data'] = $postData;
            AppFactory::pay($config)->wxNotify->handle($postData);
            echo  "success";
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "success";
        }
    }
}