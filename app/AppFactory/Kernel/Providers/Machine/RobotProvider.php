<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/9/22
 * Time: 16:14
 */

namespace app\AppFactory\Kernel\Providers\Machine;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Machine\Receive\RobotClient;

class RobotProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['robot'] = function ($app) {
            return new RobotClient($app);
        };
    }
}