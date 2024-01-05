<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:50
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Config\ConfigClient;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['config'] = function ($app) {
            return new ConfigClient($app);
        };
    }
}