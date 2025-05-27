<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 16:02
 */

namespace app\AppFactory\Kernel\Providers\Machine;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Machine\Send\MqClient;

class SendProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['sendMq'] = function ($app) {
            return new MqClient($app);
        };
    }
}