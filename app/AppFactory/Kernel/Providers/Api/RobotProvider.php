<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/9/23
 * Time: 10:45
 */

namespace app\AppFactory\Kernel\Providers\Api;


use app\AppFactory\Api\Robot\RobotClient;
use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;

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