<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/11
 * Time: 11:36
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Action\ActionVideoClient;

class ActionProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['actionVideo'] = function ($app) {
            return new ActionVideoClient($app);
        };
    }
}