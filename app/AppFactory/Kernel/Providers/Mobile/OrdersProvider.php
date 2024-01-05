<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/25
 * Time: 19:40
 */

namespace app\AppFactory\Kernel\Providers\Mobile;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Mobile\Sale\OrdersClient;

class OrdersProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['orders'] = function ($app) {
            return new OrdersClient($app);
        };
    }
}