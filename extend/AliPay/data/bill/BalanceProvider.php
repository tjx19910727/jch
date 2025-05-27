<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 17:16
 */

namespace AliPay\data\bill;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class BalanceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['balance'] = function ($app) {
            return new  BalanceClient($app);
        };
    }
}