<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/8
 * Time: 14:00
 */

namespace Jd;
use Jd\Payment\Application;

/**
 * Class Jd
 * @method static Application payment($config = []) 订单支付
 * @method static \Jd\Api\Application api($config = []) api
 * @method static \Jd\Base\Application  base($config = [])  基础信息
 * @method static \Jd\Callback\Application callback($config = [])   支付回调接收
 * @package Jd
 */
class Jd
{

    /**
     * @param $name
     * @param $config
     * @return mixed
     */
    public static function make($name, $config = [])
    {
        $name = ucwords($name);
        $application = "Jd\\{$name}\\Application";
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