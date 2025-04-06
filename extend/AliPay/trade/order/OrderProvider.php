<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:38
 */

namespace AliPay\trade\order;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class OrderProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['order'] = function ($app) {
            return new OrderClient($app);
        };
    }
}