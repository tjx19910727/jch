<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/6
 * Time: 10:07
 */

namespace app\pay\controller\notify;

use app\AppFactory\AppFactory;

class Ali
{

    /**
     * 订单支付宝支付回调
     */
    public function paymentNotify()
    {
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '回调通知数据');
            AppFactory::pay()->aliNotify->handle($postData);
        } catch (\Exception $e) {
            actionException($e,1);
            echo  "ok";
            die();
        }
    }

    public function userNotify()
    {
        $postData = input();
        actionLog($postData);
    }
}