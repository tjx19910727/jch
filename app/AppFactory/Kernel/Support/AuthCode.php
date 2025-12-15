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
        if(preg_match("/^(10|11|12|13|14|15)\d{16}$/",$code)){
            // 微信
            return 1;
        }
        if(preg_match("/^(25|26|27|28|29|30)\d{14,22}$/",$code)){
            // 支付宝
            return 2;
        }
        //商场会员积分支付
        return 9;
    }
}