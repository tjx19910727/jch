<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:42
 */

namespace app\AppFactory\Kernel\Providers\Mobile;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Mobile\Machine\CheckClient;
use app\AppFactory\Mobile\Machine\InfoClient;

class MachineProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['machineCheck'] = function ($app) {
            return new CheckClient($app);
        };
        $app['machineInfo'] = function ($app) {
            return new InfoClient($app);
        };
    }
}