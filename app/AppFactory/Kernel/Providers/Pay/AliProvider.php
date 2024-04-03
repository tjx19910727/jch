<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/2
 * Time: 9:42
 */

namespace app\AppFactory\Kernel\Providers\Pay;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Pay\Notify\AliClient;

class AliProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['aliNotify'] = function ($app) {
            return new AliClient($app);
        };
    }
}