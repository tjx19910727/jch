<?php

namespace app\AppFactory\Kernel\Providers\Management;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Currency\CurrencyClient;

class CurrencyProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['currency'] = function ($app) {
            return new CurrencyClient($app);
        };
    }
}
