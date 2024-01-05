<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 15:01
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Advertisement\AdvertisementPushClient;
use app\AppFactory\Management\Advertisement\AdvertisementResourceClient;

class AdvertisementProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['advertisementPush'] = function ($app) {
            return new AdvertisementPushClient($app);
        };
        $app['advertisementResource'] = function ($app) {
            return new AdvertisementResourceClient($app);
        };
    }
}