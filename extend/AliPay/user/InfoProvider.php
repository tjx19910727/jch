<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 17:25
 */

namespace AliPay\user;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class InfoProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['info'] = function ($app) {
            return new InfoClient($app);
        };
    }
}