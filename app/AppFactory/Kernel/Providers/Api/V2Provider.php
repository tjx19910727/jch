<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:57
 */

namespace app\AppFactory\Kernel\Providers\Api;


use app\AppFactory\Api\V2\V2Client;
use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;

class V2Provider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['v2'] = function ($app) {
            return new V2Client($app);
        };
    }
}