<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/12/31
 * Time: 14:38
 */

namespace AliPay;


/**
 * Class Factory
 * @method static data\bill\BalanceClient       Balance(array $config)   商户余额
 * @method static user\InfoClient                 Info(array $config)      用户
 *
 * @method static \AliPay\system\Application    system($config = []) 支付宝系统
 * @method static \AliPay\trade\Application     trade($config = [])   交易
 * @method static \AliPay\data\Application       data($config = [])  支付宝商户
 * @method static \AliPay\fund\Application      fund($config = [])   资金账户
 * @method static \AliPay\user\Application      user($config = [])   用户
 *
 * @package alipay
 */
class Factory
{

    /**
     * @param $name
     * @param $config
     * @return mixed
     */
    public static function make($name, $config = [])
    {
        $application = "AliPay\\{$name}\\Application";
        return new $application($config);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments = [])
    {
        return self::make($name, ...$arguments);
    }

//    protected static $nameSpace = [
//        "Relation"     => "trade\\royalty\\Relation",
//        "Order"        => "trade\\Order",
//        "Wap"          => "trade\\Wap",
//        "Balance"      => "data\\bill\\Balance",
//        "Account"      => "fund\\Account",
//        "Uni"          => "fund\\trans\\Uni",
//        "Trade"        => "Trade",
//        "TradeOrder"   => "trade\\Order",
//        "Page"         => "trade\\Page",
//        "Oauth"        => "system\\Oauth",
//        "Info"         => "user\\Info",
//    ];

    /**
     * @param $name
     * @param $config
     * @return mixed
     */
//    public static function make($name, $config)
//    {
//        $application = "alipay\\".self::$nameSpace[$name];
//        return new $application($config);
//    }

}