<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 11:14
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Common\CityClient;

class CommonProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['city'] = function ($app) {
            return new CityClient($app);
        };
    }
}