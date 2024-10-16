<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:45
 */

namespace app\AppFactory\Kernel\Support;


class AuthCode
{
    /**
     * 获取付款码来自微信或支付宝
     * @param $code
     * @return int
     */
    public static function getCodePayee($code)
    {
        $sub = substr($code,0,2);
        $len = strlen($code);
        $paymentType = 0;
        if(intval($sub) >= 10 && intval($sub) <= 15 && $len == 18){
            // 微信
            $paymentType = 1;
        }
        if(intval($sub) >= 25 && intval($sub) <= 30 && $len >= 16 && $len <= 24){
            // 支付宝
            $paymentType = 2;
        }
        return $paymentType;
    }
}