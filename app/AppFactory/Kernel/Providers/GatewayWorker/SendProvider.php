<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/26
 * Time: 14:55
 */

namespace app\AppFactory\Kernel\Providers\GatewayWorker;


use app\AppFactory\GatewayWorker\Send\HostingClient;
use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\GatewayWorker\Send\TerminalClient;

class SendProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.

        $app['sendTerminal'] = function ($app) {
            return new TerminalClient($app);
        };

        $app['sendHosting'] = function ($app) {
            return new HostingClient($app);
        };
    }
}