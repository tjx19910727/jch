<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 15:20
 */

namespace AliPay\fund\trans;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class TransProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['common'] = function ($app) {
            return new CommonClient($app);
        };
        $app['uni'] = function ($app) {
            return new UniClient($app);
        };
    }
}