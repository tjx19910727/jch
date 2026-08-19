<?php

namespace app\AppFactory\Kernel\Providers\Machine;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Machine\Laser\LaserClient;

class LaserProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['laser'] = function ($app) {
            return new LaserClient($app);
        };
    }
}
