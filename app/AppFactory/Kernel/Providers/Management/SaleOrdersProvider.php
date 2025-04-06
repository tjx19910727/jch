<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:26
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Sale\SaleOrdersClient;
use app\AppFactory\Management\Sale\SaleOrdersUnclaimedClient;

class SaleOrdersProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['saleOrders'] = function ($app) {
            return new SaleOrdersClient($app);
        };
        $app['saleOrdersUnclaimed'] = function ($app) {
            return new SaleOrdersUnclaimedClient($app);
        };
    }
}