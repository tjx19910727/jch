<?php

namespace app\AppFactory\Kernel\Providers\TimeTask;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\Revenue\RevenueClient;

class RevenueProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['revenue'] = function ($app) {
            return new RevenueClient($app);
        };
    }
}
