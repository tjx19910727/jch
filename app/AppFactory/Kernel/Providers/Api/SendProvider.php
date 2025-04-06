<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/22
 * Time: 9:18
 */

namespace app\AppFactory\Kernel\Providers\Api;


use app\AppFactory\Api\Send\CallbackClient;
use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;

class SendProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['callback'] = function ($app) {
            return new CallbackClient($app);
        };
    }
}