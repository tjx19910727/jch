<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/27
 * Time: 15:02
 */

namespace app\pay\controller\notify;


use app\AppFactory\AppFactory;
use app\pay\controller\Common;

class JdCashier extends Common
{
    /**
     * 订单支付回调
     */
    public function orderNotify()
    {
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '回调通知数据');
            if (!$postData) die("success");
            $config['key'] =  env("api.md5Key");
            $config['data'] = $postData;
            AppFactory::pay($config)->jdNotify->handle();
            echo  "success";
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "success";
        }
    }

    public function refundNotify()
    {
        $postData = input();
        actionLog($postData, '退款结果通知数据');
        $postData = json2arr($postData);
        $config['key'] =  env("api.md5Key");
        $config['data'] = $postData;
        $result = AppFactory::pay($config)->jdNotify->handleRefund();
        return $result;
    }
}