<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 10:25
 */

namespace app\AppFactory\Kernel\Providers\GatewayWorker;


use app\AppFactory\GatewayWorker\Receive\TerminalClient;
use app\AppFactory\GatewayWorker\Receive\HostingClient;
use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;

class ReceiveProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['receiveTerminal'] = function ($app) {
            return new TerminalClient($app);
        };
        $app['receiveHosting'] = function ($app) {
            return new HostingClient($app);
        };

    }
}