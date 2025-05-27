<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:09
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Trip\TripMultipleClient;
use app\AppFactory\Management\Trip\TripMultipleGoodsClient;
use app\AppFactory\Management\Trip\TripMultipleHotelClient;
use app\AppFactory\Management\Trip\TripMultipleMachineClient;

class TripProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['tripMultiple'] = function ($app) {
            return new TripMultipleClient($app);
        };
        $app['tripMultipleHotel'] = function ($app) {
            return new TripMultipleHotelClient($app);
        };
        $app['tripMultipleGoods'] = function ($app) {
            return new TripMultipleGoodsClient($app);
        };
        $app['tripMultipleMachine'] = function ($app) {
            return new TripMultipleMachineClient($app);
        };

    }
}