<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/26
 * Time: 10:40
 */

namespace app\AppFactory\Kernel\Providers\Pay;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Pay\SaleOrders\PaymentClient;

class SaleOrdersProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['payment'] = function ($app) {
            return new PaymentClient($app);
        };
    }
}