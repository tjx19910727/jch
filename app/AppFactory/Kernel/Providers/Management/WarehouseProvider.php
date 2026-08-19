<?php

namespace app\AppFactory\Kernel\Providers\Management;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Warehouse\WarehouseTransClient;

class WarehouseProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['warehouseTrans'] = function ($app) {
            return new WarehouseTransClient($app);
        };
    }
}

