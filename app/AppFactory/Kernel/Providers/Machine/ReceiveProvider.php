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
use app\AppFactory\Machine\Receive\ApiClient;
use app\AppFactory\Machine\Receive\SocketClient;
use app\AppFactory\Machine\Receive\SetClient;

class ReceiveProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['api'] = function ($app) {
            return new ApiClient($app);
        };
        $app['set'] = function ($app) {
            return new SetClient($app);
        };
        $app['socket'] = function ($app) {
            return new SocketClient($app);
        };
    }
}