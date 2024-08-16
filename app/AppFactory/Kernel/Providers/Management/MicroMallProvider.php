<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/9
 * Time: 14:49
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\MicroMall\MicroMallClient;
use app\AppFactory\Management\MicroMall\MicroMallMachineClient;

class MicroMallProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['microMall'] = function ($app) {
            return new MicroMallClient($app);
        };
        $app['microMallMachine'] = function ($app) {
            return new MicroMallMachineClient($app);
        };
    }
}