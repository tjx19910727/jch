<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 15:25
 */

namespace AliPay\fund;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class FundProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['account'] = function ($app) {
            return new AccountClient($app);
        };
    }
}