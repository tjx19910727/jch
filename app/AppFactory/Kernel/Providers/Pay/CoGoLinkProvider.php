<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/7/14
 * Time: 15:27
 */

namespace app\AppFactory\Kernel\Providers\Pay;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Pay\Notify\CoGoLinkClient;

class CoGoLinkProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['CoGoLink'] = function ($app) {
            return new CoGoLinkClient($app);
        };
    }
}