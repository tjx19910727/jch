<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:41
 */

namespace AliPay\trade\royalty;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class RelationProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['relation'] = function ($app) {
            return new RelationClient($app);
        };
    }
}