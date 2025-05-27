<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:55
 */

namespace AliPay\trade;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class TradeProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['trade'] = function ($app) {
            return new TradeClient($app);
        };
    }
}