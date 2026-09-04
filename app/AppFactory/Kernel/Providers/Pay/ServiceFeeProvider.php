<?php

namespace app\AppFactory\Kernel\Providers\Pay;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Pay\Notify\ServiceFeeAliClient;
use app\AppFactory\Pay\Notify\ServiceFeeWxClient;

class ServiceFeeProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['serviceFeeWxNotify'] = function ($app) {
            return new ServiceFeeWxClient($app);
        };
        $app['serviceFeeAliNotify'] = function ($app) {
            return new ServiceFeeAliClient($app);
        };
    }
}
