<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/31
 * Time: 15:55
 */

namespace app\AppFactory\Kernel\Providers\TimeTask;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\AuthManager\AuthManagerLogClient;

class AuthManagerProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['authManagerLog'] = function ($app) {
            return new AuthManagerLogClient($app);
        };
    }
}