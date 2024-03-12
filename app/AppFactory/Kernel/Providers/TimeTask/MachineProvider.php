<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 16:22
 */

namespace app\AppFactory\Kernel\Providers\TimeTask;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\Machine\MachineClient;

class MachineProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['machine'] = function ($app) {
            return new MachineClient($app);
        };
    }
}