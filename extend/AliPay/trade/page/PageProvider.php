<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:47
 */

namespace AliPay\trade\page;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class PageProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['page'] = function ($app){
            return new PageClient($app);
        };
    }
}