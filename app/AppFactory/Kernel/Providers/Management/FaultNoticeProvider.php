<?php

namespace app\AppFactory\Kernel\Providers\Management;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\FaultNotice\FaultNoticeClient;

class FaultNoticeProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['faultNotice'] = function ($app) {
            return new FaultNoticeClient($app);
        };
    }
}
