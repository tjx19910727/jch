<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/7/8
 * Time: 16:51
 */

namespace WeChatPayV3;


/**
 * Class PayFactory
 * @method static  \WeChatPayV3\Payment\Application  payment($config = []) 支付
 * @package app\pay\controller\PlatformPay
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
        $name = ucfirst($name);
        $application = "\\WeChatPayV3\\{$name}\\Application";
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
}