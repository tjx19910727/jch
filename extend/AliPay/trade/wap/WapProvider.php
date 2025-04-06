<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:49
 */

namespace AliPay\trade\wap;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class WapProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['wap'] = function ($app) {
            return new WapClient($app);
        };
    }
}