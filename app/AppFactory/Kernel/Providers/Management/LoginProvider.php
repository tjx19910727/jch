<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 14:10
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Login\LoginV2Client;
use app\AppFactory\Management\Login\LoginClient;

class LoginProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['login'] = function ($app) {
            return new LoginClient($app);
        };
        $app['loginV2'] = function ($app) {
            return new LoginV2Client($app);
        };
    }
}