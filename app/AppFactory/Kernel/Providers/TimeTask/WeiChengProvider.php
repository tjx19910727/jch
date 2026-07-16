<?php

namespace app\AppFactory\Kernel\Providers\TimeTask;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\WeiCheng\WeiChengClient;

class WeiChengProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['weiCheng'] = function ($app) {
            return new WeiChengClient($app);
        };
    }
}
