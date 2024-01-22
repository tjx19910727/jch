<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/10
 * Time: 14:24
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Resource\ResourceClient;

class ResourceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['resource'] = function ($app) {
            return new ResourceClient($app);
        };
    }
}