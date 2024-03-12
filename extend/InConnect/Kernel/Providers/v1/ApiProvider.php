<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 16:11
 */

namespace InConnect\Kernel\Providers\v1;


use InConnect\Kernel\Container;
use InConnect\Kernel\ServiceProviderInterface;
use InConnect\v1\api\RoutersClient;

class ApiProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['apiRouters'] = function ($app) {
            return new RoutersClient($app);
        };
    }
}