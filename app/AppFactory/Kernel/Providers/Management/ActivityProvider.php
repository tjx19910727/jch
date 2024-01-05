<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/8
 * Time: 15:18
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Activity\ActivityDiscountClient;
use app\AppFactory\Management\Activity\ActivityFullDecClient;
use app\AppFactory\Management\Activity\ActivityGoodsClient;
use app\AppFactory\Management\Activity\ActivityHgClient;
use app\AppFactory\Management\Activity\ActivityHgGoodsClient;
use app\AppFactory\Management\Activity\ActivityTimeClient;

class ActivityProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['activityDiscount'] = function ($app) {
            return new ActivityDiscountClient($app);
        };
        $app['activityFullDec'] = function ($app) {
            return new ActivityFullDecClient($app);
        };
        $app['activityGoods'] = function ($app) {
            return new ActivityGoodsClient($app);
        };
        $app['activityHg'] = function ($app) {
            return new ActivityHgClient($app);
        };
        $app['activityHgGoods'] = function ($app) {
            return new ActivityHgGoodsClient($app);
        };
        $app['activityTime'] = function ($app) {
            return new ActivityTimeClient($app);
        };
    }
}