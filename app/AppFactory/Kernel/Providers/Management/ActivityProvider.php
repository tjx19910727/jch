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
use app\AppFactory\Management\Activity\ActivityFdClient;
use app\AppFactory\Management\Activity\ActivityFdUsedClient;
use app\AppFactory\Management\Activity\ActivityLotteryClient;
use app\AppFactory\Management\Activity\ActivityLotteryUsedClient;
use app\AppFactory\Management\Activity\ActivityPickClient;
use app\AppFactory\Management\Activity\ActivityPickCodeClient;

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
        $app['activityFd'] = function ($app) {
            return new ActivityFdClient($app);
        };
        $app['activityFdUsed'] = function ($app) {
            return new ActivityFdUsedClient($app);
        };
        $app['activityPick'] = function ($app) {
            return new ActivityPickClient($app);
        };
        $app['activityPickCode'] = function ($app) {
            return new ActivityPickCodeClient($app);
        };
        $app['activityLottery'] = function ($app) {
            return new ActivityLotteryClient($app);
        };
        $app['activityLotteryUsed'] = function ($app) {
            return new ActivityLotteryUsedClient($app);
        };
    }
}