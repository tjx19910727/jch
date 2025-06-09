<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/6/7
 * Time: 15:09
 */

namespace app\AppFactory\Kernel\Providers\Pay;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Pay\Notify\PosClient;

class PosProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['posNotify'] = function ($app) {
            return new PosClient($app);
        };
    }
}