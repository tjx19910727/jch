<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:42
 */

namespace app\AppFactory\Kernel\Providers\Machine;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Machine\Receive\ActivityClient;
use app\AppFactory\Machine\Receive\ApiClient;
use app\AppFactory\Machine\Receive\MqClient;

class ReceiveProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['activity'] = function ($app) {
            return new ActivityClient($app);
        };
        $app['api'] = function ($app) {
            return new ApiClient($app);
        };
        $app['mq'] = function ($app) {
            return new MqClient($app);
        };
    }
}