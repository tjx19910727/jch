<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/21
 * Time: 9:39
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Hotel\HotelClient;

class HotelProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['hotel'] = function ($app) {
            return new HotelClient($app);
        };
    }
}