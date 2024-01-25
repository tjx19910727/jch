<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/9
 * Time: 14:00
 */

namespace Jd\Payment\Order;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

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