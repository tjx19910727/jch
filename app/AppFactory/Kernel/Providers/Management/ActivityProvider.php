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
use app\AppFactory\Management\Activity\ActivityCouponClient;
use app\AppFactory\Management\Activity\ActivityCouponUsedClient;

class ActivityProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['activityCoupon'] = function ($app) {
            return new ActivityCouponClient($app);
        };
        $app['activityCouponUsed'] = function ($app) {
            return new ActivityCouponUsedClient($app);
        };
    }
}