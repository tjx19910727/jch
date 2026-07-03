<?php

namespace app\AppFactory\Kernel\Providers\Management;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Revenue\RevenueAccountClient;
use app\AppFactory\Management\Revenue\RevenueOrderClient;
use app\AppFactory\Management\Revenue\RevenuePayChannelClient;
use app\AppFactory\Management\Revenue\RevenueRuleClient;

class RevenueProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['revenueAccount'] = function ($app) {
            return new RevenueAccountClient($app);
        };
        $app['revenueRule'] = function ($app) {
            return new RevenueRuleClient($app);
        };
        $app['revenueOrder'] = function ($app) {
            return new RevenueOrderClient($app);
        };
        $app['revenuePayChannel'] = function ($app) {
            return new RevenuePayChannelClient($app);
        };
    }
}
