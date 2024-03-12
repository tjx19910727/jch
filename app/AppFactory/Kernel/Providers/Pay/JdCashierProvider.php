<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 19:59
 */

namespace app\AppFactory\Kernel\Providers\Pay;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Pay\Notify\JdCashierClient;

class JdCashierProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['jdNotify'] = function ($app) {
            return new JdCashierClient($app);
        };
    }
}